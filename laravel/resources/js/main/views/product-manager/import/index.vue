<template>
    <a-card
        class="page-content-sub-header breadcrumb-left-border"
        :bodyStyle="{ padding: '0px', margin: '0px 16px 0' }"
    >
        <a-page-header
            title="Import Products & Stock"
            @back="() => $router.go(-1)"
            class="p-0"
        />
    </a-card>

    <div style="margin: 16px;">

        <!-- Step 1: Upload & Settings -->
        <a-card title="Step 1 — Select File & Settings" style="margin-bottom: 16px;">
            <a-row :gutter="[16, 16]">
                <a-col :xs="24" :sm="24" :md="8">
                    <a-form-item label="Excel / CSV File" required>
                        <div
                            class="upload-area"
                            :class="{ 'has-file': selectedFile }"
                            @click="() => $refs.fileInput.click()"
                            @dragover.prevent
                            @drop.prevent="onFileDrop"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                style="display:none"
                                @change="onFileChange"
                            />
                            <div v-if="!selectedFile" class="upload-placeholder">
                                <inbox-outlined style="font-size: 32px; color: #aaa;" />
                                <p style="margin: 8px 0 4px; color: #555;">Click or drag file here</p>
                                <p style="font-size: 12px; color: #aaa;">.xlsx, .xls, .csv supported</p>
                            </div>
                            <div v-else class="upload-selected">
                                <file-excel-outlined style="font-size: 28px; color: #52c41a;" />
                                <div style="margin-left: 10px;">
                                    <div style="font-weight: 600;">{{ selectedFile.name }}</div>
                                    <div style="font-size: 12px; color: #888;">{{ (selectedFile.size / 1024).toFixed(1) }} KB</div>
                                </div>
                                <a-button
                                    type="text"
                                    danger
                                    size="small"
                                    style="margin-left: auto;"
                                    @click.stop="clearFile"
                                >Remove</a-button>
                            </div>
                        </div>
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Assign Stock To Warehouse" required>
                        <a-select
                            v-model:value="selectedWarehouseId"
                            placeholder="Select warehouse"
                            style="width: 100%"
                            :loading="warehousesLoading"
                        >
                            <a-select-option
                                v-for="wh in warehouses"
                                :key="wh.id"
                                :value="wh.id"
                            >
                                {{ wh.name }}
                            </a-select-option>
                        </a-select>
                        <div style="font-size: 12px; color: #888; margin-top: 4px;">
                            Stock quantities will be assigned to this warehouse.
                        </div>
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item label="Import Mode" required>
                        <a-radio-group v-model:value="importMode" style="display: flex; flex-direction: column; gap: 8px;">
                            <a-radio value="all">
                                <strong>Create + Update</strong>
                                <div style="font-size: 12px; color: #888;">Create new products AND update stock for existing ones</div>
                            </a-radio>
                            <a-radio value="stock_only">
                                <strong>Update Stock Only</strong>
                                <div style="font-size: 12px; color: #888;">Only update stock for products that already exist</div>
                            </a-radio>
                        </a-radio-group>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="[16, 0]">
                <a-col :span="24">
                    <a-space>
                        <a-button
                            type="default"
                            :loading="previewLoading"
                            :disabled="!selectedFile"
                            @click="loadPreview"
                        >
                            <template #icon><eye-outlined /></template>
                            Preview Data
                        </a-button>
                        <a-button
                            type="primary"
                            :loading="importLoading"
                            :disabled="!selectedFile || !selectedWarehouseId || !!importDone"
                            @click="runImport"
                        >
                            <template #icon><upload-outlined /></template>
                            Start Import
                        </a-button>
                        <a-button v-if="importDone" @click="resetForm">
                            Import Another File
                        </a-button>
                    </a-space>
                </a-col>
            </a-row>
        </a-card>

        <!-- Expected Format Info -->
        <a-alert
            type="info"
            show-icon
            style="margin-bottom: 16px;"
        >
            <template #message>Expected Excel Column Order</template>
            <template #description>
                <div style="font-size: 13px;">
                    <strong>Column A</strong>: Packing ID / Item Code &nbsp;|&nbsp;
                    <strong>Column B</strong>: Item Name (required) &nbsp;|&nbsp;
                    <strong>Column C</strong>: Quantity &nbsp;|&nbsp;
                    <strong>Column D</strong>: Category &nbsp;|&nbsp;
                    <strong>Column E</strong>: Producer / Brand
                </div>
            </template>
        </a-alert>

        <!-- Preview Table -->
        <a-card
            v-if="previewData.length > 0 || previewStats"
            title="Data Preview (first 15 rows)"
            style="margin-bottom: 16px;"
        >
            <template #extra>
                <a-space v-if="previewStats">
                    <a-tag color="blue">Total Rows: {{ previewStats.total_rows }}</a-tag>
                    <a-tag color="green">Rows With Stock: {{ previewStats.with_stock }}</a-tag>
                </a-space>
            </template>
            <div style="overflow-x: auto;">
                <a-table
                    :data-source="previewData"
                    :columns="previewColumns"
                    :pagination="false"
                    size="small"
                    :row-key="(r, i) => i"
                    bordered
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'qty'">
                            <a-tag :color="record.qty > 0 ? 'green' : 'default'">{{ record.qty }}</a-tag>
                        </template>
                        <template v-if="column.key === 'category'">
                            <span style="font-size: 12px;">{{ record.category || '—' }}</span>
                        </template>
                        <template v-if="column.key === 'brand'">
                            <span style="font-size: 12px;">{{ record.brand || '—' }}</span>
                        </template>
                    </template>
                </a-table>
            </div>
        </a-card>

        <!-- Import Results -->
        <a-card v-if="importResults" title="Import Results" style="margin-bottom: 16px;">
            <a-row :gutter="[16, 16]">
                <a-col :xs="12" :sm="6">
                    <a-statistic
                        title="Products Created"
                        :value="importResults.created"
                        :value-style="{ color: '#3f8600' }"
                    >
                        <template #prefix><check-circle-outlined /></template>
                    </a-statistic>
                </a-col>
                <a-col :xs="12" :sm="6">
                    <a-statistic
                        title="Stock Updated"
                        :value="importResults.updated"
                        :value-style="{ color: '#1890ff' }"
                    >
                        <template #prefix><sync-outlined /></template>
                    </a-statistic>
                </a-col>
                <a-col :xs="12" :sm="6">
                    <a-statistic
                        title="Skipped"
                        :value="importResults.skipped"
                        :value-style="{ color: '#faad14' }"
                    >
                        <template #prefix><minus-circle-outlined /></template>
                    </a-statistic>
                </a-col>
                <a-col :xs="12" :sm="6">
                    <a-statistic
                        title="Errors"
                        :value="importResults.errors.length"
                        :value-style="{ color: importResults.errors.length > 0 ? '#cf1322' : '#3f8600' }"
                    >
                        <template #prefix><warning-outlined /></template>
                    </a-statistic>
                </a-col>
            </a-row>

            <a-alert
                v-if="importResults.errors.length === 0"
                type="success"
                message="Import completed successfully with no errors!"
                show-icon
                style="margin-top: 16px;"
            />
            <div v-else style="margin-top: 16px;">
                <a-alert
                    type="warning"
                    :message="`Import completed with ${importResults.errors.length} error(s)`"
                    show-icon
                    style="margin-bottom: 8px;"
                />
                <div
                    v-for="(err, i) in importResults.errors"
                    :key="i"
                    style="font-size: 12px; color: #cf1322; padding: 2px 0;"
                >
                    • {{ err }}
                </div>
            </div>
        </a-card>

    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { message } from "ant-design-vue";
import {
    InboxOutlined,
    EyeOutlined,
    UploadOutlined,
    CheckCircleOutlined,
    SyncOutlined,
    MinusCircleOutlined,
    WarningOutlined,
    FileExcelOutlined,
} from "@ant-design/icons-vue";

export default {
    components: {
        InboxOutlined,
        EyeOutlined,
        UploadOutlined,
        CheckCircleOutlined,
        SyncOutlined,
        MinusCircleOutlined,
        WarningOutlined,
        FileExcelOutlined,
    },
    setup() {
        const fileInput = ref(null);
        const selectedFile = ref(null);
        const selectedWarehouseId = ref(null);
        const importMode = ref("all");

        const warehouses = ref([]);
        const warehousesLoading = ref(false);

        const previewLoading = ref(false);
        const previewData = ref([]);
        const previewStats = ref(null);

        const importLoading = ref(false);
        const importResults = ref(null);
        const importDone = ref(false);

        const previewColumns = [
            { title: "Item Code", dataIndex: "item_code", key: "item_code", width: 110 },
            { title: "Product Name", dataIndex: "name", key: "name", ellipsis: true },
            { title: "Qty", dataIndex: "qty", key: "qty", width: 80, align: "center" },
            { title: "Category", dataIndex: "category", key: "category", width: 160 },
            { title: "Brand", dataIndex: "brand", key: "brand", width: 140 },
        ];

        onMounted(() => {
            warehousesLoading.value = true;
            axiosAdmin.get("product-stock-import/warehouses").then((res) => {
                warehouses.value = res.data.warehouses || [];
                if (warehouses.value.length > 0) {
                    selectedWarehouseId.value = warehouses.value[0].id;
                }
                warehousesLoading.value = false;
            }).catch(() => {
                warehousesLoading.value = false;
            });
        });

        const onFileChange = (e) => {
            const f = e.target.files[0];
            if (f) {
                selectedFile.value = f;
                previewData.value = [];
                previewStats.value = null;
                importResults.value = null;
                importDone.value = false;
            }
        };

        const onFileDrop = (e) => {
            const f = e.dataTransfer.files[0];
            if (f && (f.name.endsWith('.xlsx') || f.name.endsWith('.xls') || f.name.endsWith('.csv'))) {
                selectedFile.value = f;
                previewData.value = [];
                previewStats.value = null;
                importResults.value = null;
                importDone.value = false;
            } else {
                message.error("Please drop an Excel or CSV file.");
            }
        };

        const clearFile = () => {
            selectedFile.value = null;
            previewData.value = [];
            previewStats.value = null;
            importResults.value = null;
            importDone.value = false;
            if (fileInput.value) fileInput.value.value = '';
        };

        const loadPreview = () => {
            if (!selectedFile.value) return;
            previewLoading.value = true;
            previewData.value = [];
            previewStats.value = null;

            const fd = new FormData();
            fd.append("file", selectedFile.value);

            axiosAdmin.post("product-stock-import/preview", fd, {
                headers: { "Content-Type": "multipart/form-data" },
            }).then((res) => {
                previewData.value = res.data.preview || [];
                previewStats.value = { total_rows: res.data.total_rows, with_stock: res.data.with_stock };
                previewLoading.value = false;
            }).catch((err) => {
                previewLoading.value = false;
                message.error(err?.response?.data?.message || "Failed to read file.");
            });
        };

        const runImport = () => {
            if (!selectedFile.value || !selectedWarehouseId.value) {
                message.warning("Please select a file and a warehouse first.");
                return;
            }
            importLoading.value = true;
            importResults.value = null;

            const fd = new FormData();
            fd.append("file", selectedFile.value);
            fd.append("warehouse_id", selectedWarehouseId.value);
            fd.append("import_mode", importMode.value);

            axiosAdmin.post("product-stock-import/import", fd, {
                headers: { "Content-Type": "multipart/form-data" },
                timeout: 300000,
            }).then((res) => {
                importResults.value = res.data.results;
                importDone.value = true;
                importLoading.value = false;
                const r = res.data.results;
                message.success(`Done! Created: ${r.created}, Updated: ${r.updated}, Skipped: ${r.skipped}`);
            }).catch((err) => {
                importLoading.value = false;
                message.error(err?.response?.data?.message || "Import failed. Please check your file.");
            });
        };

        const resetForm = () => {
            clearFile();
        };

        return {
            fileInput,
            selectedFile,
            selectedWarehouseId,
            importMode,
            warehouses,
            warehousesLoading,
            previewLoading,
            previewData,
            previewStats,
            previewColumns,
            importLoading,
            importResults,
            importDone,
            onFileChange,
            onFileDrop,
            clearFile,
            loadPreview,
            runImport,
            resetForm,
        };
    },
};
</script>

<style scoped>
.upload-area {
    border: 2px dashed #d9d9d9;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    background: #fafafa;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.upload-area:hover {
    border-color: #1890ff;
    background: #e6f7ff;
}
.upload-area.has-file {
    border-color: #52c41a;
    background: #f6ffed;
}
.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.upload-selected {
    display: flex;
    align-items: center;
    width: 100%;
    text-align: left;
}
</style>
