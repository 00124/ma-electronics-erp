<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Journal Entries" class="p-0">
                <template #extra>
                    <a-button type="primary" @click="openAddModal"><PlusOutlined /> New Journal Entry</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size:12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Accounting</a-breadcrumb-item>
                <a-breadcrumb-item>Journal Entries</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <!-- Filters -->
        <a-row :gutter="16" class="mb-20">
            <a-col :span="8">
                <a-range-picker v-model:value="dateRange" style="width:100%" @change="loadEntries" />
            </a-col>
        </a-row>

        <a-spin :spinning="loading">
            <a-table :dataSource="entries" :columns="columns" :pagination="pagination" @change="handleTableChange" rowKey="id" size="middle" :scroll="{ x: 900 }">
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'entry_date'">{{ formatDate(record.entry_date) }}</template>
                    <template v-if="column.key === 'total_debit'">
                        <span class="text-blue-600 font-semibold">{{ formatAmount(record.lines?.reduce((s,l)=>s+(+l.debit),0)) }}</span>
                    </template>
                    <template v-if="column.key === 'status'">
                        <a-tag :color="record.status==='posted'?'green':'orange'">{{ record.status }}</a-tag>
                    </template>
                    <template v-if="column.key === 'action'">
                        <a-space>
                            <a-button size="small" type="link" @click="viewEntry(record)"><EyeOutlined /></a-button>
                            <a-popconfirm title="Delete this entry?" @confirm="deleteEntry(record.id)">
                                <a-button size="small" type="link" danger><DeleteOutlined /></a-button>
                            </a-popconfirm>
                        </a-space>
                    </template>
                </template>
            </a-table>
        </a-spin>
    </a-card>

    <!-- New Journal Entry Modal -->
    <a-modal v-model:open="addModalVisible" title="New Journal Entry" width="860px" @ok="saveEntry" :confirmLoading="saving" okText="Post Entry">
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col :span="8">
                    <a-form-item label="Date" required>
                        <a-date-picker v-model:value="entryForm.entry_date" style="width:100%" />
                    </a-form-item>
                </a-col>
                <a-col :span="8">
                    <a-form-item label="Reference">
                        <a-input v-model:value="entryForm.reference" placeholder="Invoice #, PO #..." />
                    </a-form-item>
                </a-col>
                <a-col :span="8">
                    <a-form-item label="Description">
                        <a-input v-model:value="entryForm.description" placeholder="Entry description" />
                    </a-form-item>
                </a-col>
            </a-row>

            <!-- Lines -->
            <a-table :dataSource="entryForm.lines" :columns="lineColumns" :pagination="false" size="small" rowKey="key">
                <template #bodyCell="{ column, record, index }">
                    <template v-if="column.key === 'account_id'">
                        <a-select v-model:value="record.account_id" style="width:100%" show-search :filter-option="filterAccount" placeholder="Select account">
                            <a-select-option v-for="a in allAccounts" :key="a.id" :value="a.id">
                                {{ a.account_code }} - {{ a.account_name }}
                            </a-select-option>
                        </a-select>
                    </template>
                    <template v-if="column.key === 'description'">
                        <a-input v-model:value="record.description" placeholder="Line note" />
                    </template>
                    <template v-if="column.key === 'debit'">
                        <a-input-number v-model:value="record.debit" :min="0" :precision="2" style="width:100%" @change="()=>record.credit=record.debit>0?0:record.credit" />
                    </template>
                    <template v-if="column.key === 'credit'">
                        <a-input-number v-model:value="record.credit" :min="0" :precision="2" style="width:100%" @change="()=>record.debit=record.credit>0?0:record.debit" />
                    </template>
                    <template v-if="column.key === 'remove'">
                        <a-button type="link" danger size="small" @click="removeLine(index)" :disabled="entryForm.lines.length<=2"><MinusCircleOutlined /></a-button>
                    </template>
                </template>
                <template #footer>
                    <div class="flex justify-between items-center">
                        <a-button type="dashed" size="small" @click="addLine"><PlusOutlined /> Add Line</a-button>
                        <div style="font-weight:600">
                            <span class="mr-4">Total Debit: <span class="text-blue-600">{{ totalDebit.toFixed(2) }}</span></span>
                            <span>Total Credit: <span class="text-green-600">{{ totalCredit.toFixed(2) }}</span></span>
                            <span v-if="!isBalanced" class="ml-4 text-red-500">⚠ Not balanced</span>
                            <span v-else class="ml-4 text-green-600">✓ Balanced</span>
                        </div>
                    </div>
                </template>
            </a-table>
        </a-form>
    </a-modal>

    <!-- View Entry Modal -->
    <a-modal v-model:open="viewModalVisible" title="Journal Entry Details" width="720px" :footer="null">
        <template v-if="viewingEntry">
            <a-descriptions :column="2" bordered size="small" class="mb-16">
                <a-descriptions-item label="Entry #">{{ viewingEntry.entry_number }}</a-descriptions-item>
                <a-descriptions-item label="Date">{{ formatDate(viewingEntry.entry_date) }}</a-descriptions-item>
                <a-descriptions-item label="Reference">{{ viewingEntry.reference || '-' }}</a-descriptions-item>
                <a-descriptions-item label="Status"><a-tag color="green">{{ viewingEntry.status }}</a-tag></a-descriptions-item>
                <a-descriptions-item label="Description" :span="2">{{ viewingEntry.description || '-' }}</a-descriptions-item>
            </a-descriptions>
            <a-table :dataSource="viewingEntry.lines" :columns="viewLineColumns" :pagination="false" size="small" rowKey="id">
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'debit'">
                        <span v-if="+record.debit > 0" class="text-blue-600 font-semibold">{{ formatAmount(record.debit) }}</span>
                    </template>
                    <template v-if="column.key === 'credit'">
                        <span v-if="+record.credit > 0" class="text-green-600 font-semibold">{{ formatAmount(record.credit) }}</span>
                    </template>
                </template>
                <template #summary>
                    <a-table-summary-row>
                        <a-table-summary-cell :index="0" :col-span="2"><b>Total</b></a-table-summary-cell>
                        <a-table-summary-cell :index="2" class="text-blue-600 font-bold">{{ formatAmount(viewingEntry.lines?.reduce((s,l)=>s+(+l.debit),0)) }}</a-table-summary-cell>
                        <a-table-summary-cell :index="3" class="text-green-600 font-bold">{{ formatAmount(viewingEntry.lines?.reduce((s,l)=>s+(+l.credit),0)) }}</a-table-summary-cell>
                    </a-table-summary-row>
                </template>
            </a-table>
        </template>
    </a-modal>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { PlusOutlined, DeleteOutlined, EyeOutlined, MinusCircleOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import AdminPageHeader from '../../../../common/layouts/AdminPageHeader.vue';
import axios from 'axios';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader, PlusOutlined, DeleteOutlined, EyeOutlined, MinusCircleOutlined },
    setup() {
        const loading = ref(false);
        const saving = ref(false);
        const entries = ref([]);
        const allAccounts = ref([]);
        const dateRange = ref(null);
        const pagination = ref({ current: 1, pageSize: 20, total: 0 });

        const addModalVisible = ref(false);
        const viewModalVisible = ref(false);
        const viewingEntry = ref(null);

        const newLine = () => ({ key: Date.now() + Math.random(), account_id: null, description: '', debit: 0, credit: 0 });
        const entryForm = ref({ entry_date: dayjs(), reference: '', description: '', lines: [newLine(), newLine()] });

        const totalDebit = computed(() => entryForm.value.lines.reduce((s, l) => s + (+l.debit || 0), 0));
        const totalCredit = computed(() => entryForm.value.lines.reduce((s, l) => s + (+l.credit || 0), 0));
        const isBalanced = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.01 && totalDebit.value > 0);

        const columns = [
            { title: 'Entry #', dataIndex: 'entry_number', key: 'entry_number', width: 150 },
            { title: 'Date', key: 'entry_date', width: 110 },
            { title: 'Description', dataIndex: 'description', key: 'description', ellipsis: true },
            { title: 'Reference', dataIndex: 'reference', key: 'reference', width: 130 },
            { title: 'Total', key: 'total_debit', width: 120 },
            { title: 'Status', key: 'status', width: 100 },
            { title: 'Action', key: 'action', width: 100, fixed: 'right' },
        ];

        const lineColumns = [
            { title: 'Account', key: 'account_id', width: 260 },
            { title: 'Description', key: 'description' },
            { title: 'Debit', key: 'debit', width: 130 },
            { title: 'Credit', key: 'credit', width: 130 },
            { title: '', key: 'remove', width: 50 },
        ];

        const viewLineColumns = [
            { title: 'Account', dataIndex: ['account', 'account_name'], key: 'account' },
            { title: 'Description', dataIndex: 'description', key: 'description' },
            { title: 'Debit', key: 'debit', width: 130 },
            { title: 'Credit', key: 'credit', width: 130 },
        ];

        const formatDate = (d) => d ? dayjs(d).format('DD MMM YYYY') : '-';
        const formatAmount = (v) => Number(v || 0).toLocaleString('en-PK', { minimumFractionDigits: 2 });
        const filterAccount = (input, option) => {
            const label = option.children?.() ?? '';
            return String(label).toLowerCase().includes(input.toLowerCase());
        };

        const loadAccounts = async () => {
            const res = await axios.get('/api/accounting/coa');
            allAccounts.value = (res.data.flat || res.data.data?.flat || []).filter(a => a.parent_id);
        };

        const loadEntries = async () => {
            loading.value = true;
            try {
                const params = { per_page: pagination.value.pageSize, page: pagination.value.current };
                if (dateRange.value?.[0]) { params.date_from = dateRange.value[0].format('YYYY-MM-DD'); params.date_to = dateRange.value[1].format('YYYY-MM-DD'); }
                const res = await axios.get('/api/accounting/journal-entries', { params });
                entries.value = res.data.data || res.data;
                pagination.value.total = res.data.total || entries.value.length;
            } catch (e) { message.error('Failed to load entries'); }
            finally { loading.value = false; }
        };

        const handleTableChange = (pag) => { pagination.value.current = pag.current; loadEntries(); };

        const openAddModal = () => {
            entryForm.value = { entry_date: dayjs(), reference: '', description: '', lines: [newLine(), newLine()] };
            addModalVisible.value = true;
        };

        const addLine = () => entryForm.value.lines.push(newLine());
        const removeLine = (i) => entryForm.value.lines.splice(i, 1);

        const saveEntry = async () => {
            if (!isBalanced.value) { message.warning('Debit and Credit totals must be equal'); return; }
            saving.value = true;
            try {
                const payload = {
                    entry_date: entryForm.value.entry_date.format('YYYY-MM-DD'),
                    reference: entryForm.value.reference,
                    description: entryForm.value.description,
                    lines: entryForm.value.lines.filter(l => l.account_id),
                };
                await axios.post('/api/accounting/journal-entries', payload);
                message.success('Journal entry posted');
                addModalVisible.value = false;
                loadEntries();
            } catch (e) { message.error(e.response?.data?.message || 'Error saving entry'); }
            finally { saving.value = false; }
        };

        const viewEntry = (record) => { viewingEntry.value = record; viewModalVisible.value = true; };

        const deleteEntry = async (id) => {
            try { await axios.delete(`/api/accounting/journal-entries/${id}`); message.success('Deleted'); loadEntries(); }
            catch (e) { message.error('Cannot delete'); }
        };

        onMounted(() => { loadAccounts(); loadEntries(); });

        return { loading, saving, entries, allAccounts, dateRange, pagination, addModalVisible, viewModalVisible, viewingEntry, entryForm, totalDebit, totalCredit, isBalanced, columns, lineColumns, viewLineColumns, formatDate, formatAmount, filterAccount, loadEntries, handleTableChange, openAddModal, addLine, removeLine, saveEntry, viewEntry, deleteEntry };
    }
});
</script>
