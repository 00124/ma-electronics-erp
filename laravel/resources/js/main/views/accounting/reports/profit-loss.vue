<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Profit & Loss Statement" class="p-0">
                <template #extra>
                    <a-button @click="print"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Profit & Loss</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <a-row :gutter="16" class="mb-20" align="middle">
            <a-col :span="6"><label class="block text-xs text-gray-500 mb-1">From Date</label><a-date-picker v-model:value="filters.date_from" style="width:100%" /></a-col>
            <a-col :span="6"><label class="block text-xs text-gray-500 mb-1">To Date</label><a-date-picker v-model:value="filters.date_to" style="width:100%" /></a-col>
            <a-col :span="4"><a-button type="primary" @click="load" class="mt-20" :loading="loading"><SearchOutlined /> Generate</a-button></a-col>
        </a-row>

        <a-spin :spinning="loading">
            <div id="printable-area">
                <div class="text-center mb-20">
                    <h2 style="margin:0">Profit & Loss Statement</h2>
                    <p style="margin:0;color:#666">{{ formatDate(data.date_from) }} to {{ formatDate(data.date_to) }}</p>
                </div>

                <!-- Revenue -->
                <a-card class="mb-16" :bodyStyle="{ padding: '12px 16px' }">
                    <div class="flex justify-between font-bold text-base mb-8" style="color:#16a34a">
                        <span>Revenue</span><span>{{ fmt(data.total_revenue) }}</span>
                    </div>
                    <div v-for="r in incomeRows" :key="r.account_code" class="flex justify-between py-4 border-b" style="padding-left:16px">
                        <span class="text-gray-700">{{ r.account_code }} - {{ r.account_name }}</span>
                        <span>{{ fmt(r.net) }}</span>
                    </div>
                </a-card>

                <!-- COGS -->
                <a-card class="mb-16" :bodyStyle="{ padding: '12px 16px' }">
                    <div class="flex justify-between font-bold text-base mb-8" style="color:#dc2626">
                        <span>Cost of Goods Sold</span><span>{{ fmt(data.total_cogs) }}</span>
                    </div>
                    <div v-for="r in cogsRows" :key="r.account_code" class="flex justify-between py-4 border-b" style="padding-left:16px">
                        <span class="text-gray-700">{{ r.account_code }} - {{ r.account_name }}</span>
                        <span>{{ fmt(Math.abs(r.net)) }}</span>
                    </div>
                </a-card>

                <!-- Gross Profit -->
                <div class="flex justify-between font-bold text-lg py-12 px-16 rounded mb-16" :style="{ background: data.gross_profit >= 0 ? '#dcfce7' : '#fee2e2', color: data.gross_profit >= 0 ? '#15803d' : '#dc2626' }">
                    <span>Gross Profit</span><span>{{ fmt(data.gross_profit) }}</span>
                </div>

                <!-- Expenses -->
                <a-card class="mb-16" :bodyStyle="{ padding: '12px 16px' }">
                    <div class="flex justify-between font-bold text-base mb-8" style="color:#d97706">
                        <span>Operating Expenses</span><span>{{ fmt(data.total_expenses) }}</span>
                    </div>
                    <div v-for="r in expenseRows" :key="r.account_code" class="flex justify-between py-4 border-b" style="padding-left:16px">
                        <span class="text-gray-700">{{ r.account_code }} - {{ r.account_name }}</span>
                        <span>{{ fmt(Math.abs(r.net)) }}</span>
                    </div>
                </a-card>

                <!-- Net Profit -->
                <div class="flex justify-between font-bold text-xl py-16 px-16 rounded" :style="{ background: data.net_profit >= 0 ? '#bbf7d0' : '#fecaca', color: data.net_profit >= 0 ? '#15803d' : '#dc2626' }">
                    <span>NET PROFIT / LOSS</span>
                    <span>{{ fmt(data.net_profit) }}</span>
                </div>
            </div>
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
        const loading = ref(false);
        const filters = ref({ date_from: dayjs().startOf('year'), date_to: dayjs() });
        const data = ref({ data: [], total_revenue: 0, total_cogs: 0, gross_profit: 0, total_expenses: 0, net_profit: 0, date_from: '', date_to: '' });

        const incomeRows  = computed(() => (data.value.data || []).filter(r => r.account_type === 'Income'));
        const cogsRows    = computed(() => (data.value.data || []).filter(r => r.account_type === 'COGS'));
        const expenseRows = computed(() => (data.value.data || []).filter(r => r.account_type === 'Expense'));

        const fmt = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/reports/profit-loss', { params: { date_from: filters.value.date_from?.format('YYYY-MM-DD'), date_to: filters.value.date_to?.format('YYYY-MM-DD') } });
                data.value = res.data;
            } catch (e) {} finally { loading.value = false; }
        };

        const print = () => window.print();
        onMounted(load);
        return { loading, filters, data, incomeRows, cogsRows, expenseRows, fmt, formatDate, load, print };
    }
});
</script>
