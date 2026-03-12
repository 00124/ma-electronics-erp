<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="General Ledger" class="p-0">
                <template #extra>
                    <a-button @click="print"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>General Ledger</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <a-row :gutter="16" class="mb-20" align="middle">
            <a-col :span="7">
                <label class="block text-xs text-gray-500 mb-1">Account</label>
                <a-select v-model:value="filters.account_id" style="width:100%" show-search :filter-option="filterOption" placeholder="Select account...">
                    <a-select-option v-for="a in allAccounts" :key="a.id" :value="a.id">{{ a.account_code }} — {{ a.account_name }}</a-select-option>
                </a-select>
            </a-col>
            <a-col :span="5">
                <label class="block text-xs text-gray-500 mb-1">From Date</label>
                <a-date-picker v-model:value="filters.date_from" style="width:100%" />
            </a-col>
            <a-col :span="5">
                <label class="block text-xs text-gray-500 mb-1">To Date</label>
                <a-date-picker v-model:value="filters.date_to" style="width:100%" />
            </a-col>
            <a-col :span="4">
                <a-button type="primary" @click="load" class="mt-20" :loading="loading" :disabled="!filters.account_id">
                    <SearchOutlined /> Generate
                </a-button>
            </a-col>
        </a-row>

        <a-spin :spinning="loading">
            <div id="printable-area" v-if="reportData.lines.length > 0 || generated">
                <div class="text-center mb-20">
                    <h2 style="margin:0">General Ledger</h2>
                    <h3 style="margin:4px 0;color:#333">{{ reportData.account?.account_code }} — {{ reportData.account?.account_name }}</h3>
                    <p style="margin:0;color:#666">{{ formatDate(reportData.date_from) }} to {{ formatDate(reportData.date_to) }}</p>
                </div>

                <a-table :dataSource="reportData.lines" :columns="columns" :pagination="false" size="middle" rowKey="(r,i) => i" :scroll="{ x: 800 }">
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'debit'">
                            <span v-if="+record.debit !== 0" class="text-blue-600">{{ fmt(record.debit) }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </template>
                        <template v-if="column.key === 'credit'">
                            <span v-if="+record.credit !== 0" class="text-green-600">{{ fmt(record.credit) }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </template>
                        <template v-if="column.key === 'running_balance'">
                            <span :class="record.running_balance >= 0 ? 'text-blue-700 font-semibold' : 'text-red-600 font-semibold'">
                                {{ fmt(Math.abs(record.running_balance)) }} {{ record.running_balance >= 0 ? 'Dr' : 'Cr' }}
                            </span>
                        </template>
                    </template>
                    <template #summary>
                        <a-table-summary-row>
                            <a-table-summary-cell :index="0" :col-span="3"><b>TOTAL</b></a-table-summary-cell>
                            <a-table-summary-cell :index="3" align="right"><b class="text-blue-600">{{ fmt(totalDebit) }}</b></a-table-summary-cell>
                            <a-table-summary-cell :index="4" align="right"><b class="text-green-600">{{ fmt(totalCredit) }}</b></a-table-summary-cell>
                            <a-table-summary-cell :index="5" align="right">
                                <b :class="closingBalance >= 0 ? 'text-blue-700' : 'text-red-600'">
                                    {{ fmt(Math.abs(closingBalance)) }} {{ closingBalance >= 0 ? 'Dr' : 'Cr' }}
                                </b>
                            </a-table-summary-cell>
                        </a-table-summary-row>
                    </template>
                </a-table>

                <a-empty v-if="reportData.lines.length === 0" description="No transactions found for this period" class="mt-30" />
            </div>

            <a-empty v-if="!generated && !loading" description="Select an account and click Generate" class="mt-40" />
        </a-spin>
    </a-card>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { PrinterOutlined, SearchOutlined } from '@ant-design/icons-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader, PrinterOutlined, SearchOutlined },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading  = ref(false);
        const generated = ref(false);
        const allAccounts = ref([]);
        const filters  = ref({ account_id: null, date_from: dayjs().startOf('year'), date_to: dayjs() });
        const reportData = ref({ account: null, lines: [], date_from: '', date_to: '' });

        const columns = [
            { title: 'Date',        dataIndex: 'entry_date',    key: 'entry_date',    width: 110 },
            { title: 'Entry No.',   dataIndex: 'entry_number',  key: 'entry_number',  width: 140 },
            { title: 'Description', dataIndex: 'je_description',key: 'je_description' },
            { title: 'Debit',       key: 'debit',  width: 140, align: 'right' },
            { title: 'Credit',      key: 'credit', width: 140, align: 'right' },
            { title: 'Balance',     key: 'running_balance', width: 150, align: 'right' },
        ];

        const fmt = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';
        const filterOption = (input, option) => option.children?.()[0]?.children?.toLowerCase().includes(input.toLowerCase());

        const totalDebit  = computed(() => reportData.value.lines.reduce((s, r) => s + +r.debit, 0));
        const totalCredit = computed(() => reportData.value.lines.reduce((s, r) => s + +r.credit, 0));
        const closingBalance = computed(() => totalDebit.value - totalCredit.value);

        const loadAccounts = async () => {
            const res = await axiosAdmin.get('accounting/coa');
            allAccounts.value = (res.data?.flat || []).filter(a => a.parent_id);
        };

        const load = async () => {
            loading.value = true;
            generated.value = true;
            try {
                const res = await axiosAdmin.get('accounting/reports/general-ledger', {
                    params: {
                        account_id: filters.value.account_id,
                        date_from:  filters.value.date_from?.format('YYYY-MM-DD'),
                        date_to:    filters.value.date_to?.format('YYYY-MM-DD'),
                    }
                });
                reportData.value = res.data;
            } catch (e) {} finally { loading.value = false; }
        };

        const print = () => window.print();
        onMounted(loadAccounts);
        return { loading, generated, allAccounts, filters, reportData, columns, fmt, formatDate, filterOption, totalDebit, totalCredit, closingBalance, load, print };
    }
});
</script>
