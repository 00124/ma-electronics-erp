<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Trial Balance" class="p-0">
                <template #extra>
                    <a-button @click="print"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Trial Balance</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <!-- Filters -->
        <a-row :gutter="16" class="mb-20" align="middle">
            <a-col :span="6"><label class="block text-xs text-gray-500 mb-1">From Date</label><a-date-picker v-model:value="filters.date_from" style="width:100%" /></a-col>
            <a-col :span="6"><label class="block text-xs text-gray-500 mb-1">To Date</label><a-date-picker v-model:value="filters.date_to" style="width:100%" /></a-col>
            <a-col :span="4"><a-button type="primary" @click="load" class="mt-20" :loading="loading"><SearchOutlined /> Generate</a-button></a-col>
        </a-row>

        <a-spin :spinning="loading">
            <div id="printable-area">
                <div class="text-center mb-20">
                    <h2 style="margin:0">Trial Balance</h2>
                    <p style="margin:0;color:#666">{{ formatDate(reportData.date_from) }} to {{ formatDate(reportData.date_to) }}</p>
                </div>

                <a-table :dataSource="reportData.data" :columns="columns" :pagination="false" size="middle" rowKey="id" :scroll="{ x: 700 }">
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'account_name'">
                            <span :style="{ paddingLeft: record.parent_id ? '20px' : '0', fontWeight: record.parent_id ? 'normal' : '700' }">
                                {{ record.account_name }}
                            </span>
                        </template>
                        <template v-if="column.key === 'account_type'">
                            <a-tag :color="typeColor(record.account_type)">{{ record.account_type }}</a-tag>
                        </template>
                        <template v-if="column.key === 'total_debit'">
                            <span v-if="+record.total_debit !== 0" class="text-blue-600">{{ fmt(record.total_debit) }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </template>
                        <template v-if="column.key === 'total_credit'">
                            <span v-if="+record.total_credit !== 0" class="text-green-600">{{ fmt(record.total_credit) }}</span>
                            <span v-else class="text-gray-300">-</span>
                        </template>
                    </template>
                    <template #summary>
                        <a-table-summary-row>
                            <a-table-summary-cell :index="0" :col-span="2"><b>TOTAL</b></a-table-summary-cell>
                            <a-table-summary-cell :index="2"><b class="text-blue-600">{{ fmt(reportData.total_debit) }}</b></a-table-summary-cell>
                            <a-table-summary-cell :index="3"><b class="text-green-600">{{ fmt(reportData.total_credit) }}</b></a-table-summary-cell>
                        </a-table-summary-row>
                    </template>
                </a-table>
            </div>
        </a-spin>
    </a-card>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import { PrinterOutlined, SearchOutlined } from '@ant-design/icons-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import axios from 'axios';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader, PrinterOutlined, SearchOutlined },
    setup() {
        const loading = ref(false);
        const filters = ref({ date_from: dayjs().startOf('year'), date_to: dayjs() });
        const reportData = ref({ data: [], total_debit: 0, total_credit: 0, date_from: '', date_to: '' });

        const columns = [
            { title: 'Code', dataIndex: 'account_code', key: 'account_code', width: 110 },
            { title: 'Account Name', key: 'account_name' },
            { title: 'Type', key: 'account_type', width: 120 },
            { title: 'Debit', key: 'total_debit', width: 140, align: 'right' },
            { title: 'Credit', key: 'total_credit', width: 140, align: 'right' },
        ];

        const fmt = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';
        const typeColor = (t) => ({ Asset: 'blue', Liability: 'red', Equity: 'purple', Income: 'green', Expense: 'orange', COGS: 'volcano' })[t] || 'default';

        const load = async () => {
            loading.value = true;
            try {
                const res = await axios.get('/api/v1/accounting/reports/trial-balance', { params: { date_from: filters.value.date_from?.format('YYYY-MM-DD'), date_to: filters.value.date_to?.format('YYYY-MM-DD') } });
                reportData.value = res.data.data;
            } catch (e) {} finally { loading.value = false; }
        };

        const print = () => window.print();

        onMounted(load);
        return { loading, filters, reportData, columns, fmt, formatDate, typeColor, load, print };
    }
});
</script>
