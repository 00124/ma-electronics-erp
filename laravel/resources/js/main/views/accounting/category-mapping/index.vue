<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Category Accounting Mapping" class="p-0">
                <template #extra>
                    <a-button type="primary" :loading="saving" @click="saveAll" data-testid="btn-save-all">
                        <SaveOutlined /> Save All Changes
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Category Mapping</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">

        <!-- Info Banner -->
        <a-alert
            type="info"
            show-icon
            class="mb-20"
            message="Category Accounting Mapping"
            description="Define which Chart of Accounts to use for each product category. Journal entries for sales, purchases, COGS, and inventory will automatically use these accounts."
        />

        <!-- Filter -->
        <a-row class="mb-16" :gutter="16" align="middle">
            <a-col :span="8">
                <a-input-search
                    v-model:value="search"
                    placeholder="Filter categories..."
                    allow-clear
                    data-testid="input-search"
                />
            </a-col>
            <a-col :span="16" class="text-right">
                <a-tag color="blue">{{ filteredCategories.length }} categories</a-tag>
                <a-tag color="orange" v-if="dirtyCount > 0">{{ dirtyCount }} unsaved changes</a-tag>
            </a-col>
        </a-row>

        <a-spin :spinning="loading">
            <a-table
                :dataSource="filteredCategories"
                :columns="columns"
                :pagination="{ pageSize: 20, showSizeChanger: true }"
                rowKey="id"
                size="middle"
                :scroll="{ x: 1200 }"
                bordered
            >
                <template #bodyCell="{ column, record }">

                    <!-- Category Name -->
                    <template v-if="column.key === 'name'">
                        <span class="font-semibold">{{ record.name }}</span>
                    </template>

                    <!-- Inventory Account -->
                    <template v-if="column.key === 'inventory_account_id'">
                        <a-select
                            v-model:value="record.inventory_account_id"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                            :options="accountOptions('Asset')"
                            allow-clear
                            placeholder="Select account..."
                            @change="markDirty(record)"
                            :data-testid="`select-inventory-${record.id}`"
                        />
                    </template>

                    <!-- Purchase Account -->
                    <template v-if="column.key === 'purchase_account_id'">
                        <a-select
                            v-model:value="record.purchase_account_id"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                            :options="accountOptions('Asset')"
                            allow-clear
                            placeholder="Select account..."
                            @change="markDirty(record)"
                            :data-testid="`select-purchase-${record.id}`"
                        />
                    </template>

                    <!-- Sales Account -->
                    <template v-if="column.key === 'sales_account_id'">
                        <a-select
                            v-model:value="record.sales_account_id"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                            :options="accountOptions('Income')"
                            allow-clear
                            placeholder="Select account..."
                            @change="markDirty(record)"
                            :data-testid="`select-sales-${record.id}`"
                        />
                    </template>

                    <!-- COGS Account -->
                    <template v-if="column.key === 'cogs_account_id'">
                        <a-select
                            v-model:value="record.cogs_account_id"
                            style="width: 100%"
                            show-search
                            option-filter-prop="label"
                            :options="accountOptions('COGS')"
                            allow-clear
                            placeholder="Select account..."
                            @change="markDirty(record)"
                            :data-testid="`select-cogs-${record.id}`"
                        />
                    </template>

                    <!-- Status -->
                    <template v-if="column.key === 'status'">
                        <a-tag v-if="record._dirty" color="orange">Unsaved</a-tag>
                        <a-tag v-else-if="isMapped(record)" color="green">Mapped</a-tag>
                        <a-tag v-else color="red">Not Mapped</a-tag>
                    </template>

                    <!-- Actions -->
                    <template v-if="column.key === 'actions'">
                        <a-button
                            size="small"
                            type="primary"
                            :loading="record._saving"
                            @click="saveOne(record)"
                            :disabled="!record._dirty"
                            :data-testid="`btn-save-${record.id}`"
                        >
                            Save
                        </a-button>
                    </template>

                </template>
            </a-table>
        </a-spin>
    </a-card>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { SaveOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';

export default defineComponent({
    name: 'CategoryAccountingMapping',
    components: { AdminPageHeader, SaveOutlined },

    setup() {
        const axiosAdmin = window.axiosAdmin;
        const loading   = ref(false);
        const saving    = ref(false);
        const search    = ref('');
        const categories = ref([]);
        const accounts   = ref([]);

        // ─── TABLE COLUMNS ─────────────────────────────────────────────
        const columns = [
            { title: 'Category',          key: 'name',                 dataIndex: 'name',    width: 200, fixed: 'left' },
            { title: 'Inventory Account', key: 'inventory_account_id', width: 230,
              tooltip: 'Asset account debited when inventory is received (used in COGS entries)' },
            { title: 'Purchase Account',  key: 'purchase_account_id',  width: 230,
              tooltip: 'Asset account debited when purchasing stock from supplier' },
            { title: 'Sales Account',     key: 'sales_account_id',     width: 230,
              tooltip: 'Revenue account credited when goods are sold' },
            { title: 'COGS Account',      key: 'cogs_account_id',      width: 230,
              tooltip: 'Cost account debited when goods are sold (cost of goods sold)' },
            { title: 'Status',            key: 'status',               width: 100 },
            { title: 'Action',            key: 'actions',              width: 80,  fixed: 'right' },
        ];

        // ─── FILTERED LIST ─────────────────────────────────────────────
        const filteredCategories = computed(() => {
            if (!search.value) return categories.value;
            const q = search.value.toLowerCase();
            return categories.value.filter(c => c.name.toLowerCase().includes(q));
        });

        const dirtyCount = computed(() => categories.value.filter(c => c._dirty).length);

        // ─── ACCOUNT OPTIONS BY TYPE ───────────────────────────────────
        const accountOptions = (type) => {
            const filtered = type
                ? accounts.value.filter(a => a.account_type === type)
                : accounts.value;
            return filtered.map(a => ({
                value: a.id,
                label: `${a.account_code} — ${a.account_name}`,
            }));
        };

        const isMapped = (record) =>
            record.inventory_account_id &&
            record.purchase_account_id  &&
            record.sales_account_id     &&
            record.cogs_account_id;

        const markDirty = (record) => { record._dirty = true; };

        // ─── LOAD DATA ─────────────────────────────────────────────────
        const load = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/category-mappings');
                const data = res.data ?? res;
                categories.value = (data.categories || []).map(c => ({
                    ...c,
                    _dirty:  false,
                    _saving: false,
                }));
                accounts.value = data.accounts || [];
            } catch (e) {
                message.error('Failed to load category mappings');
            } finally {
                loading.value = false;
            }
        };

        // ─── SAVE SINGLE ROW ───────────────────────────────────────────
        const saveOne = async (record) => {
            record._saving = true;
            try {
                await axiosAdmin.put(`accounting/category-mappings/${record.id}`, {
                    sales_account_id:     record.sales_account_id     || null,
                    cogs_account_id:      record.cogs_account_id      || null,
                    inventory_account_id: record.inventory_account_id || null,
                    purchase_account_id:  record.purchase_account_id  || null,
                });
                record._dirty  = false;
                message.success(`${record.name} mapping saved`);
            } catch (e) {
                message.error(`Failed to save ${record.name}`);
            } finally {
                record._saving = false;
            }
        };

        // ─── SAVE ALL DIRTY ROWS ───────────────────────────────────────
        const saveAll = async () => {
            const dirty = categories.value.filter(c => c._dirty);
            if (!dirty.length) { message.info('No changes to save'); return; }

            saving.value = true;
            try {
                const mappings = dirty.map(c => ({
                    id:                   c.id,
                    sales_account_id:     c.sales_account_id     || null,
                    cogs_account_id:      c.cogs_account_id      || null,
                    inventory_account_id: c.inventory_account_id || null,
                    purchase_account_id:  c.purchase_account_id  || null,
                }));
                const res = await axiosAdmin.post('accounting/category-mappings/bulk', { mappings });
                const updated = res.data?.updated ?? res.updated ?? dirty.length;
                dirty.forEach(c => { c._dirty = false; });
                message.success(`${updated} category mappings saved successfully`);
            } catch (e) {
                message.error('Failed to save all mappings');
            } finally {
                saving.value = false;
            }
        };

        onMounted(load);

        return {
            loading, saving, search, categories, accounts,
            columns, filteredCategories, dirtyCount,
            accountOptions, isMapped, markDirty, saveOne, saveAll,
        };
    },
});
</script>
