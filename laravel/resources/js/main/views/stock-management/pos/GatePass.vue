<template>
    <a-modal
        :open="visible"
        :centered="true"
        :maskClosable="false"
        title="Gate Pass"
        width="500px"
        @cancel="onClose"
    >
        <div id="gate-pass-content">
            <div style="max-width: 500px; margin: 0 auto; font-family: Arial, sans-serif; font-size: 13px;" v-if="order && order.xid">
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
                            <td style="padding: 3px 0;"><strong>Warehouse:</strong> {{ sellingWarehouseName || selectedWarehouse.name }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 3px 0;" colspan="2"><strong>Customer:</strong> {{ order.user ? order.user.name : 'Walk-in Customer' }}</td>
                        </tr>
                        <tr v-if="order.staff_member">
                            <td style="padding: 3px 0;" colspan="2"><strong>Issued By:</strong> {{ order.staff_member.name }}</td>
                        </tr>
                    </tbody>
                </table>

                <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
                    <thead>
                        <tr style="background: #333; color: #fff;">
                            <td style="padding: 5px; border: 1px solid #333; width: 5%">#</td>
                            <td style="padding: 5px; border: 1px solid #333;">Product Name</td>
                            <td style="padding: 5px; border: 1px solid #333; text-align: center; width: 15%">Qty</td>
                            <td style="padding: 5px; border: 1px solid #333; text-align: right; width: 20%">Unit Price</td>
                            <td style="padding: 5px; border: 1px solid #333; text-align: right; width: 20%">Total</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in order.items" :key="item.xid">
                            <td style="padding: 4px 5px; border: 1px solid #ccc;">{{ index + 1 }}</td>
                            <td style="padding: 4px 5px; border: 1px solid #ccc;">{{ item.product ? item.product.name : '-' }}</td>
                            <td style="padding: 4px 5px; border: 1px solid #ccc; text-align: center;">{{ item.quantity }} {{ item.unit ? item.unit.short_name : '' }}</td>
                            <td style="padding: 4px 5px; border: 1px solid #ccc; text-align: right;">{{ formatAmountCurrency(item.unit_price) }}</td>
                            <td style="padding: 4px 5px; border: 1px solid #ccc; text-align: right;">{{ formatAmountCurrency(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="padding: 5px; border: 1px solid #ccc; text-align: right; font-weight: bold;">Grand Total</td>
                            <td style="padding: 5px; border: 1px solid #ccc; text-align: right; font-weight: bold;">{{ formatAmountCurrency(order.total) }}</td>
                        </tr>
                    </tfoot>
                </table>

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
import { defineComponent } from "vue";
import { PrinterOutlined } from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";

export default defineComponent({
    props: ["visible", "order", "sellingWarehouseName"],
    emits: ["closed"],
    components: { PrinterOutlined },
    setup(props, { emit }) {
        const { formatAmountCurrency, formatDate, selectedWarehouse } = common();

        const onClose = () => emit("closed");

        const printGatePass = () => {
            const content = document.getElementById("gate-pass-content").innerHTML;
            const printWindow = window.open("", "_blank", "width=600,height=800");
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

        return { formatAmountCurrency, formatDate, selectedWarehouse, onClose, printGatePass };
    },
});
</script>
