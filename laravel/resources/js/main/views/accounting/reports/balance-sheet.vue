<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Balance Sheet" class="p-0">
                <template #extra>
                    <a-button @click="print"><PrinterOutlined /> Print</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Balance Sheet</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <a-row :gutter="16" class="mb-20" align="middle">
            <a-col :span="6"><label class="block text-xs text-gray-500 mb-1">As of Date</label><a-date-picker v-model:value="filters.as_of" style="width:100%" /></a-col>
            <a-col :span="4"><a-button type="primary" @click="load" class="mt-20" :loading="loading"><SearchOutlined /> Generate</a-button></a-col>
        </a-row>

        <a-spin :spinning="loading">
            <div id="printable-area">
                <div class="text-center mb-20">
                    <h2 style="margin:0">Balance Sheet</h2>
                    <p style="margin:0;color:#666">As of {{ formatDate(data.as_of) }}</p>
                </div>

                <a-row :gutter="24">
                    <!-- Assets -->
                    <a-col :span="12">
                        <a-card :bodyStyle="{ padding: '12px 16px' }">
                            <div class="font-bold text-base mb-12" style="color:#1d4ed8">ASSETS</div>
                            <div v-for="r in assetRows" :key="r.account_code" class="flex justify-between py-4 border-b">
                                <span>{{ r.account_code }} - {{ r.account_name }}</span>
                                <span class="font-medium">{{ fmt(r.balance) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-base mt-12 pt-8 border-t-2">
                                <span>Total Assets</span><span style="color:#1d4ed8">{{ fmt(data.total_assets) }}</span>
                            </div>
                        </a-card>
                    </a-col>

                    <!-- Liabilities + Equity -->
                    <a-col :span="12">
                        <a-card class="mb-16" :bodyStyle="{ padding: '12px 16px' }">
                            <div class="font-bold text-base mb-12" style="color:#dc2626">LIABILITIES</div>
                            <div v-for="r in liabilityRows" :key="r.account_code" class="flex justify-between py-4 border-b">
                                <span>{{ r.account_code }} - {{ r.account_name }}</span>
                                <span class="font-medium">{{ fmt(Math.abs(r.balance)) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-base mt-12 pt-8 border-t-2">
                                <span>Total Liabilities</span><span style="color:#dc2626">{{ fmt(data.total_liabilities) }}</span>
                            </div>
                        </a-card>

                        <a-card :bodyStyle="{ padding: '12px 16px' }">
                            <div class="font-bold text-base mb-12" style="color:#7c3aed">EQUITY</div>
                            <div v-for="r in equityRows" :key="r.account_code" class="flex justify-between py-4 border-b">
                                <span>{{ r.account_code }} - {{ r.account_name }}</span>
                                <span class="font-medium">{{ fmt(Math.abs(r.balance)) }}</span>
                            </div>
                            <div class="flex justify-between font-bold text-base mt-12 pt-8 border-t-2">
                                <span>Total Equity</span><span style="color:#7c3aed">{{ fmt(data.total_equity) }}</span>
                            </div>
                        </a-card>

                        <!-- Balance Check -->
                        <div class="mt-16 p-12 rounded font-bold text-center" :style="{ background: isBalanced ? '#dcfce7' : '#fee2e2', color: isBalanced ? '#15803d' : '#dc2626' }">
                            <div>Liabilities + Equity: {{ fmt(+data.total_liabilities + +data.total_equity) }}</div>
                            <div class="text-sm font-normal mt-4">{{ isBalanced ? '✓ Balance Sheet is balanced' : '⚠ Balance Sheet is not balanced' }}</div>
                        </div>
                    </a-col>
                </a-row>
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
        const filters = ref({ as_of: dayjs() });
        const data = ref({ data: [], total_assets: 0, total_liabilities: 0, total_equity: 0, as_of: '' });

        const assetRows     = computed(() => (data.value.data || []).filter(r => r.account_type === 'Asset'));
        const liabilityRows = computed(() => (data.value.data || []).filter(r => r.account_type === 'Liability'));
        const equityRows    = computed(() => (data.value.data || []).filter(r => r.account_type === 'Equity'));
        const isBalanced    = computed(() => Math.abs((+data.value.total_assets) - (+data.value.total_liabilities + +data.value.total_equity)) < 1);

        const fmt = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '';

        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/reports/balance-sheet', { params: { as_of: filters.value.as_of?.format('YYYY-MM-DD') } });
                data.value = res.data;
            } catch (e) {} finally { loading.value = false; }
        };

        const print = () => window.print();
        onMounted(load);
        return { loading, filters, data, assetRows, liabilityRows, equityRows, isBalanced, fmt, formatDate, load, print };
    }
});
</script>
