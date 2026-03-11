<template>
    <a-modal
        :open="visible"
        :centered="true"
        :maskClosable="false"
        title="Gate Pass"
        width="580px"
        @cancel="onClose"
    >
        <div id="gate-pass-content">
            <div style="max-width: 560px; margin: 0 auto; font-family: Arial, sans-serif; font-size: 13px;" v-if="order && order.xid">
                <div style="text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 10px;">
                    <h2 style="margin: 0; font-size: 18px;">{{ selectedWarehouse.name }}</h2>
                    <p style="margin: 2px 0; font-size: 12px;">{{ selectedWarehouse.address }}</p>
                    <h3 style="margin: 6px 0 0; font-size: 15px; letter-spacing: 1px;">GATE PASS</h3>
                </div>

                <table style="width: 100%; margin-bottom: 10px; border-collapse: collapse;">
                    <tbody>
                        <tr>
                            <td style="width: 50%; padding: 3px 0;"><strong>Gate Pass No:</strong> GP-{{ order.invoice_number }}</td>
                            <td style="width: 50%; padding: 3px 0;"><strong>Date:</strong> {{ formatDate(order.order_date) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;"><strong>Invoice No:</strong> {{ order.invoice_number }}</td>
                            <td style="padding: 3px 0;"><strong>POS Warehouse:</strong> {{ sellingWarehouseName || selectedWarehouse.name }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;" colspan="2"><strong>Customer:</strong> {{ order.user ? order.user.name : 'Walk-in Customer' }}</td>
                        </tr>
                        <tr v-if="order.user && order.user.phone">
                            <td style="padding: 3px 0;" colspan="2"><strong>Customer Phone:</strong> {{ order.user.phone }}</td>
                        </tr>
                        <tr v-if="order.staff_member">
                            <td style="padding: 3px 0;" colspan="2"><strong>Salesman:</strong> {{ order.staff_member.name }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Per-warehouse sections -->
                <div
                    v-for="(group, gIndex) in itemsByWarehouse"
                    :key="group.warehouseKey"
                    :style="{ marginBottom: '16px', border: '1px solid #91caff', borderRadius: '4px', overflow: 'hidden' }"
                >
                    <!-- Warehouse header -->
                    <div style="background: #1677ff; color: #fff; padding: 5px 10px; font-size: 13px; font-weight: bold;">
                        Warehouse / Store: {{ group.warehouseName }}
                        <span style="float: right; font-size: 11px; font-weight: normal;">Section {{ gIndex + 1 }} of {{ itemsByWarehouse.length }}</span>
                    </div>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #e6f4ff;">
                                <td style="padding: 5px; border: 1px solid #ccc; width: 8%">#</td>
                                <td style="padding: 5px; border: 1px solid #ccc;">Product Name</td>
                                <td style="padding: 5px; border: 1px solid #ccc; text-align: center; width: 20%">Qty</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in group.items" :key="item.xid">
                                <td style="padding: 4px 5px; border: 1px solid #ccc;">{{ index + 1 }}</td>
                                <td style="padding: 4px 5px; border: 1px solid #ccc;">{{ item.product ? item.product.name : '-' }}</td>
                                <td style="padding: 4px 5px; border: 1px solid #ccc; text-align: center;">{{ item.quantity }} {{ item.unit ? item.unit.short_name : '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Signature row -->
                <table style="width: 100%; margin-top: 30px;">
                    <tbody>
                        <tr>
                            <td style="width: 50%; text-align: center; padding-top: 40px;">
                                <div style="border-top: 1px solid #333; display: inline-block; width: 80%; padding-top: 4px;">
                                    <strong>Authorized By</strong><br/>
                                    <span style="font-size: 11px;">Name &amp; Signature</span>
                                </div>
                            </td>
                            <td style="width: 50%; text-align: center; padding-top: 40px;">
                                <div style="border-top: 1px solid #333; display: inline-block; width: 80%; padding-top: 4px;">
                                    <strong>Received By</strong><br/>
                                    <span style="font-size: 11px;">Name &amp; Signature</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <template #footer>
            <div class="footer-button">
                <a-button @click="onClose">Close</a-button>
                <a-button type="primary" @click="printGatePass">
                    <template #icon><PrinterOutlined /></template>
                    Print Gate Pass
                </a-button>
            </div>
        </template>
    </a-modal>
</template>

<script>
import { defineComponent, computed } from "vue";
import { PrinterOutlined } from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";

export default defineComponent({
    props: ["visible", "order", "sellingWarehouseName"],
    emits: ["closed"],
    components: { PrinterOutlined },
    setup(props, { emit }) {
        const { formatAmountCurrency, formatDate, selectedWarehouse } = common();

        const onClose = () => emit("closed");

        const itemsByWarehouse = computed(() => {
            if (!props.order || !props.order.items) return [];

            const groups = {};
            props.order.items.forEach(item => {
                const warehouseName = (item.warehouse && item.warehouse.name)
                    ? item.warehouse.name
                    : (props.sellingWarehouseName || selectedWarehouse.value?.name || 'Default');
                const warehouseKey = (item.warehouse && item.warehouse.xid)
                    ? item.warehouse.xid
                    : 'default';

                if (!groups[warehouseKey]) {
                    groups[warehouseKey] = {
                        warehouseKey,
                        warehouseName,
                        items: [],
                        total: 0,
                    };
                }
                groups[warehouseKey].items.push(item);
                groups[warehouseKey].total += item.subtotal || 0;
            });

            return Object.values(groups);
        });

        const printGatePass = () => {
            const content = document.getElementById("gate-pass-content").innerHTML;
            const printWindow = window.open("", "_blank", "width=650,height=900");
            printWindow.document.write(`
                <html><head><title>Gate Pass</title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 13px; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    td { padding: 4px 5px; }
                    @media print { body { margin: 0; } }
                </style>
                </head><body>${content}</body></html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); printWindow.close(); }, 300);
        };

        return { formatAmountCurrency, formatDate, selectedWarehouse, onClose, printGatePass, itemsByWarehouse };
    },
});
</script>
