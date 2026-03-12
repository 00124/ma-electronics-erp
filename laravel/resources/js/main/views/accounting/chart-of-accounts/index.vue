<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Chart Of Accounts" class="p-0">
                <template #extra>
                    <a-button type="primary" @click="openAddModal()">
                        <PlusOutlined /> {{ $t('common.add') }} Account
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">{{ $t('menu.dashboard') }}</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Chart of Accounts</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <a-spin :spinning="loading">
            <a-table
                :dataSource="flatAccounts"
                :columns="columns"
                :pagination="false"
                :scroll="{ x: 900 }"
                rowKey="id"
                size="middle"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'account_name'">
                        <span :style="{ paddingLeft: record.parent_id ? '24px' : '0', fontWeight: record.parent_id ? 'normal' : '600' }">
                            {{ record.account_name }}
                        </span>
                    </template>
                    <template v-if="column.key === 'account_type'">
                        <a-tag :color="typeColor(record.account_type)">{{ record.account_type }}</a-tag>
                    </template>
                    <template v-if="column.key === 'status'">
                        <a-badge :status="record.status ? 'success' : 'error'" :text="record.status ? 'Active' : 'Inactive'" />
                    </template>
                    <template v-if="column.key === 'action'">
                        <a-space>
                            <a-button size="small" type="link" @click="openEditModal(record)"><EditOutlined /></a-button>
                            <a-popconfirm title="Delete this account?" @confirm="deleteAccount(record.id)" v-if="record.parent_id">
                                <a-button size="small" type="link" danger><DeleteOutlined /></a-button>
                            </a-popconfirm>
                        </a-space>
                    </template>
                </template>
            </a-table>
        </a-spin>
    </a-card>

    <!-- Add/Edit Modal -->
    <a-modal v-model:open="modalVisible" :title="editingId ? 'Edit Account' : 'Add Account'" @ok="saveAccount" :confirmLoading="saving">
        <a-form layout="vertical" :model="form">
            <a-form-item label="Account Code" required>
                <a-input v-model:value="form.account_code" placeholder="e.g. 11001" />
            </a-form-item>
            <a-form-item label="Account Name" required>
                <a-input v-model:value="form.account_name" placeholder="Account name" />
            </a-form-item>
            <a-form-item label="Account Type" required>
                <a-select v-model:value="form.account_type" style="width:100%">
                    <a-select-option value="Asset">Asset</a-select-option>
                    <a-select-option value="Liability">Liability</a-select-option>
                    <a-select-option value="Equity">Equity</a-select-option>
                    <a-select-option value="Income">Income (Revenue)</a-select-option>
                    <a-select-option value="Expense">Expense</a-select-option>
                    <a-select-option value="COGS">Cost of Goods Sold</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Parent Account">
                <a-select v-model:value="form.parent_id" style="width:100%" allow-clear placeholder="None (Top-level)">
                    <a-select-option v-for="a in parentAccounts" :key="a.id" :value="a.id">
                        {{ a.account_code }} - {{ a.account_name }}
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Description">
                <a-textarea v-model:value="form.description" :rows="2" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>

<script>
import { defineComponent, ref, onMounted, computed } from 'vue';
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import { useI18n } from 'vue-i18n';

export default defineComponent({
    components: { AdminPageHeader, PlusOutlined, EditOutlined, DeleteOutlined },
    setup() {
        const axiosAdmin = window.axiosAdmin;
        const { t } = useI18n();
        const loading = ref(false);
        const saving = ref(false);
        const flatAccounts = ref([]);
        const modalVisible = ref(false);
        const editingId = ref(null);
        const form = ref({ account_code: '', account_name: '', account_type: 'Asset', parent_id: null, description: '' });

        const columns = [
            { title: 'Code', dataIndex: 'account_code', key: 'account_code', width: 110 },
            { title: 'Account Name', dataIndex: 'account_name', key: 'account_name' },
            { title: 'Type', key: 'account_type', width: 130 },
            { title: 'Description', dataIndex: 'description', key: 'description', ellipsis: true },
            { title: 'Status', key: 'status', width: 100 },
            { title: 'Action', key: 'action', width: 100, fixed: 'right' },
        ];

        const parentAccounts = computed(() => flatAccounts.value.filter(a => !a.parent_id));

        const typeColor = (type) => {
            const map = { Asset: 'blue', Liability: 'red', Equity: 'purple', Income: 'green', Expense: 'orange', COGS: 'volcano' };
            return map[type] || 'default';
        };

        const loadAccounts = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('accounting/coa');
                flatAccounts.value = res.data.flat || [];
            } catch (e) { message.error('Failed to load accounts'); }
            finally { loading.value = false; }
        };

        const openAddModal = () => {
            editingId.value = null;
            form.value = { account_code: '', account_name: '', account_type: 'Asset', parent_id: null, description: '' };
            modalVisible.value = true;
        };

        const openEditModal = (record) => {
            editingId.value = record.id;
            form.value = { account_code: record.account_code, account_name: record.account_name, account_type: record.account_type, parent_id: record.parent_id, description: record.description || '' };
            modalVisible.value = true;
        };

        const saveAccount = async () => {
            if (!form.value.account_code || !form.value.account_name) { message.warning('Code and Name are required'); return; }
            saving.value = true;
            try {
                if (editingId.value) {
                    await axiosAdmin.put(`accounting/coa/${editingId.value}`, form.value);
                    message.success('Account updated');
                } else {
                    await axiosAdmin.post('accounting/coa', form.value);
                    message.success('Account created');
                }
                modalVisible.value = false;
                loadAccounts();
            } catch (e) { message.error(e.response?.data?.message || 'Error saving account'); }
            finally { saving.value = false; }
        };

        const deleteAccount = async (id) => {
            try {
                await axiosAdmin.delete(`accounting/coa/${id}`);
                message.success('Account deleted');
                loadAccounts();
            } catch (e) { message.error(e.response?.data?.message || 'Cannot delete'); }
        };

        onMounted(loadAccounts);

        return { loading, saving, flatAccounts, modalVisible, editingId, form, columns, parentAccounts, typeColor, openAddModal, openEditModal, saveAccount, deleteAccount, t };
    }
});
</script>
