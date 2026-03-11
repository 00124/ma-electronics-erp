<template>
    <a-modal
        :open="visible"
        :centered="true"
        :maskClosable="false"
        :title="$t('common.print_invoice')"
        width="750px"
        @cancel="onClose"
    >
        <div id="pos-invoice">
            <div class="inv-page" v-if="order && order.xid">

                <!-- ══ HEADER ══════════════════════════════════════════ -->
                <table class="inv-header-table">
                    <tr>
                        <td class="inv-header-left">
                            <img class="inv-logo" :src="selectedWarehouse.logo_url" :alt="selectedWarehouse.name" />
                            <div class="inv-company-name">{{ selectedWarehouse.name }}</div>
                            <div class="inv-company-sub" v-if="selectedWarehouse.address">{{ selectedWarehouse.address }}</div>
                            <div class="inv-company-sub" v-if="selectedWarehouse.phone"><strong>Contact:</strong> {{ selectedWarehouse.phone }}</div>
                            <div class="inv-company-sub" v-if="selectedWarehouse.email">{{ selectedWarehouse.email }}</div>
                        </td>
                        <td class="inv-header-right">
                            <div class="inv-bill-box">
                                <div class="inv-bill-title">BILL TO:</div>
                                <div class="inv-bill-row" v-if="order.user?.customer_code">
                                    <strong>Code:</strong> {{ order.user.customer_code }}
                                </div>
                                <div class="inv-bill-row">
                                    <strong>Name:</strong> {{ order.user?.name || '-' }}
                                </div>
                                <div class="inv-bill-row" v-if="order.user?.phone">
                                    <strong>Phone:</strong> {{ order.user.phone }}
                                </div>
                                <div class="inv-bill-row" v-if="order.user?.address">
                                    <strong>Address:</strong> {{ order.user.address }}
                                </div>
                                <div class="inv-bill-row" v-if="order.staff_member?.name" style="margin-top:6px;">
                                    <strong>Salesman:</strong> {{ order.staff_member.name }}
                                </div>
                                <div class="inv-bill-row" v-if="sellingWarehouseName">
                                    <strong>Location:</strong> {{ sellingWarehouseName }}
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- ══ INVOICE TITLE ════════════════════════════════════ -->
                <div class="inv-title">
                    {{ order.order_type === 'purchases' ? 'PURCHASE INVOICE'
                     : order.order_type === 'purchase-returns' ? 'PURCHASE RETURN'
                     : order.order_type === 'sales-returns' ? 'SALES RETURN'
                     : order.order_type === 'quotations' ? 'QUOTATION'
                     : 'INVOICE' }}
                </div>

                <table class="inv-meta-table">
                    <thead>
                        <tr>
                            <th>Invoice Type</th>
                            <th>Invoice Number</th>
                            <th>Location</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ invoiceTypeCode }}</td>
                            <td><strong>{{ order.invoice_number }}</strong></td>
                            <td>{{ sellingWarehouseName || selectedWarehouse.name }}</td>
                            <td>{{ formatDate(order.order_date) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- ══ PRODUCT TABLE ════════════════════════════════════ -->
                <table class="inv-items-table">
                    <thead>
                        <tr>
                            <th class="col-code">Code</th>
                            <th class="col-desc">Description</th>
                            <th class="col-qty text-center">QTY</th>
                            <th class="col-price text-right">PRICE</th>
                            <th class="col-total text-right">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="inv-item-row" v-for="(item, index) in order.items" :key="item.xid">
                            <td class="col-code">{{ item.product?.item_code || '-' }}</td>
                            <td class="col-desc">{{ item.product?.name || '-' }}</td>
                            <td class="col-qty text-center">{{ item.quantity }}{{ item.unit?.short_name || '' }}</td>
                            <td class="col-price text-right">{{ formatAmountCurrency(item.unit_price) }}</td>
                            <td class="col-total text-right">{{ formatAmountCurrency(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- ══ PAYMENT + TOTALS ═════════════════════════════════ -->
                <table class="inv-payment-section" v-if="order.order_type !== 'quotations'">
                    <tr>
                        <td class="inv-pay-left">
                            <table class="inv-pay-modes">
                                <tr v-for="pm in paymentBreakdown" :key="pm.label">
                                    <td>{{ pm.label }}</td>
                                    <td class="text-right">{{ formatAmountCurrency(pm.amount) }}</td>
                                </tr>
                                <tr class="inv-due-row">
                                    <td><strong>Due Amount</strong></td>
                                    <td class="text-right"><strong>{{ formatAmountCurrency(order.due_amount) }}</strong></td>
                                </tr>
                            </table>
                        </td>
                        <td class="inv-pay-right">
                            <table class="inv-totals-box">
                                <tr>
                                    <td>Qty</td>
                                    <td class="text-right">{{ order.total_quantity }}</td>
                                </tr>
                                <tr v-if="order.discount > 0">
                                    <td>Discount</td>
                                    <td class="text-right">{{ formatAmountCurrency(order.discount) }}</td>
                                </tr>
                                <tr v-if="order.tax_amount > 0">
                                    <td>Tax</td>
                                    <td class="text-right">{{ formatAmountCurrency(order.tax_amount) }}</td>
                                </tr>
                                <tr class="inv-grand-row">
                                    <td>TOTAL</td>
                                    <td class="text-right">{{ formatAmountCurrency(order.total) }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- ══ NOTES ════════════════════════════════════════════ -->
                <div class="inv-notes" v-if="order.notes">
                    <strong>Notes:</strong> {{ order.notes }}
                </div>

                <!-- ══ FOOTER ══════════════════════════════════════════ -->
                <div class="inv-footer-divider"></div>
                <table class="inv-footer-table">
                    <tr>
                        <td class="inv-footer-left">
                            <div v-if="order.staff_member?.name"><strong>{{ order.staff_member.name }}</strong></div>
                            <div v-if="selectedWarehouse.email">{{ selectedWarehouse.email }}</div>
                            <div>Printed: {{ printTime }}</div>
                        </td>
                        <td class="inv-footer-center">
                            <div class="inv-delivered-stamp">DELIVERED</div>
                        </td>
                        <td class="inv-footer-right">
                            <div>Page 1 of 1</div>
                        </td>
                    </tr>
                </table>

            </div>
        </div>

        <template #footer>
            <div class="footer-button">
                <a-button
                    v-if="order?.user?.email && isVerified"
                    type="primary"
                    @click="sendInvoiceMail(order.xid, selectedLang)"
                    :loading="isSending"
                >
                    <template #icon><SendOutlined /></template>
                    {{ $t("common.send_invoice") }}
                </a-button>
                <a-button type="primary" @click="printInvoice">
                    <template #icon><PrinterOutlined /></template>
                    {{ $t("common.print_invoice") }}
                </a-button>
                <a-button @click="gatePassVisible = true" style="margin-left: 4px;">Gate Pass</a-button>
            </div>
        </template>
    </a-modal>

    <GatePass
        :visible="gatePassVisible"
        :order="order"
        :sellingWarehouseName="sellingWarehouseName"
        @closed="gatePassVisible = false"
    />
</template>

<script>
import { ref, computed, defineComponent, onMounted } from "vue";
import { PrinterOutlined, SendOutlined } from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";
import BarcodeGenerator from "../../../../common/components/barcode/BarcodeGenerator.vue";
import QRcodeGenerator from "../../../../common/components/barcode/QRcodeGenerator.vue";
import GatePass from "./GatePass.vue";
import { notification } from "ant-design-vue";
import { useI18n } from "vue-i18n";

const INVOICE_STYLES = `
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,sans-serif;font-size:12px;color:#000;background:#fff;}
.inv-page{width:100%;max-width:700px;margin:0 auto;padding:10px;}
.inv-header-table{width:100%;border-collapse:collapse;margin-bottom:10px;}
.inv-header-left{width:50%;vertical-align:top;}
.inv-logo{max-width:120px;max-height:65px;display:block;margin-bottom:4px;}
.inv-company-name{font-size:14px;font-weight:bold;margin-bottom:2px;}
.inv-company-sub{font-size:11px;color:#333;line-height:1.5;}
.inv-header-right{width:50%;vertical-align:top;text-align:right;}
.inv-bill-box{display:inline-block;text-align:left;border:1px solid #000;padding:7px 10px;min-width:190px;}
.inv-bill-title{font-size:11px;font-weight:bold;text-decoration:underline;margin-bottom:5px;}
.inv-bill-row{font-size:11px;line-height:1.7;}
.inv-bill-row strong{display:inline-block;min-width:70px;}
.inv-title{text-align:center;font-size:18px;font-weight:bold;letter-spacing:3px;margin:10px 0 6px;text-transform:uppercase;}
.inv-meta-table{width:100%;border-collapse:collapse;margin-bottom:10px;}
.inv-meta-table th{background:#000;color:#fff;border:1px solid #000;padding:5px 8px;text-align:center;font-size:11px;}
.inv-meta-table td{border:1px solid #000;padding:5px 8px;text-align:center;font-size:11px;}
.inv-items-table{width:100%;border-collapse:collapse;margin-bottom:10px;}
.inv-items-table th{background:#000;color:#fff;border:1px solid #000;padding:5px 7px;font-size:11px;}
.inv-items-table td{border:1px solid #000;padding:5px 7px;font-size:11px;vertical-align:top;}
.inv-items-table tr:nth-child(even) td{background:#f7f7f7;}
.col-code{width:9%;}
.col-desc{width:38%;}
.col-qty{width:11%;}
.col-price{width:20%;}
.col-total{width:22%;}
.text-center{text-align:center;}
.text-right{text-align:right;}
.inv-payment-section{width:100%;border-collapse:collapse;margin-bottom:10px;}
.inv-pay-left{width:55%;vertical-align:top;padding-right:8px;}
.inv-pay-right{width:45%;vertical-align:top;}
.inv-pay-modes{width:100%;border-collapse:collapse;}
.inv-pay-modes td{border:1px solid #000;padding:5px 8px;font-size:11px;}
.inv-pay-modes td.text-right{text-align:right;font-weight:bold;}
.inv-due-row td{background:#f0f0f0;}
.inv-totals-box{width:100%;border-collapse:collapse;border:2px solid #000;}
.inv-totals-box td{border:1px solid #000;padding:5px 10px;font-size:12px;}
.inv-totals-box td.text-right{text-align:right;font-weight:bold;}
.inv-grand-row td{background:#000;color:#fff;font-weight:bold;font-size:13px;}
.inv-notes{border:1px solid #ccc;padding:5px 8px;font-size:11px;margin-bottom:8px;}
.inv-footer-divider{border-top:1px solid #000;margin:8px 0 6px;}
.inv-footer-table{width:100%;border-collapse:collapse;}
.inv-footer-table td{vertical-align:top;font-size:10px;color:#333;padding:0 4px;}
.inv-footer-left{width:38%;}
.inv-footer-center{width:24%;text-align:center;}
.inv-footer-right{width:38%;text-align:right;}
.inv-delivered-stamp{display:inline-block;border:3px solid #000;border-radius:4px;padding:5px 12px;font-size:14px;font-weight:bold;letter-spacing:3px;color:#000;opacity:.2;transform:rotate(-10deg);margin-top:2px;}
@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}
`;

export default defineComponent({
    props: ["visible", "order", "sellingWarehouseName"],
    emits: ["closed", "success"],
    components: { PrinterOutlined, BarcodeGenerator, QRcodeGenerator, SendOutlined, GatePass },
    setup(props, { emit }) {
        const { t } = useI18n();
        const { formatAmountCurrency, formatDate, selectedWarehouse, selectedLang } = common();

        const isSending = ref(false);
        const isVerified = ref("");
        const gatePassVisible = ref(false);

        onMounted(() => {
            axiosAdmin.get("verified-email").then((response) => {
                isVerified.value = response.data?.verified;
            });
        });

        const printTime = computed(() => {
            const now = new Date();
            return now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        });

        const invoiceTypeCode = computed(() => {
            const t = props.order?.order_type;
            if (t === 'sales') return 'INVCR';
            if (t === 'purchases') return 'PUR';
            if (t === 'purchase-returns') return 'PUR-RET';
            if (t === 'sales-returns') return 'SALE-RET';
            if (t === 'quotations') return 'QUOT';
            if (t === 'stock-transfers') return 'TRNSFR';
            return (t || '').toUpperCase();
        });

        const paymentBreakdown = computed(() => {
            const payments = props.order?.order_payments || [];
            const groups = {};
            for (const op of payments) {
                const name = op.payment?.payment_mode?.name || 'Other';
                groups[name] = (groups[name] || 0) + (op.amount || 0);
            }
            return Object.entries(groups).map(([label, amount]) => ({ label, amount }));
        });

        const onClose = () => emit("closed");

        const sendInvoiceMail = async (xid, lang) => {
            isSending.value = true;
            try {
                await axiosAdmin.get(`send-mail/${xid}/${lang}`);
                notification.success({ message: t("common.success"), description: t("common.mail_sent_successfully"), duration: 4.5 });
            } catch {
                notification.error({ message: t("common.error"), description: t("common.failed_to_send_mail"), duration: 4.5 });
            } finally {
                isSending.value = false;
            }
        };

        const printInvoice = () => {
            const invoiceContent = document.getElementById("pos-invoice").innerHTML;
            const newWindow = window.open("", "", "height=900,width=750");
            newWindow.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><style>${INVOICE_STYLES}</style></head><body>`);
            newWindow.document.write(invoiceContent);
            newWindow.document.write("</body></html>");
            newWindow.document.close();
            setTimeout(() => { newWindow.print(); }, 400);
        };

        return {
            onClose,
            formatDate,
            selectedWarehouse,
            formatAmountCurrency,
            printInvoice,
            selectedLang,
            sendInvoiceMail,
            isSending,
            isVerified,
            gatePassVisible,
            invoiceTypeCode,
            paymentBreakdown,
            printTime,
        };
    },
});
</script>

<style>
.inv-page { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
.inv-header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.inv-header-left { width: 50%; vertical-align: top; }
.inv-logo { max-width: 120px; max-height: 65px; display: block; margin-bottom: 4px; }
.inv-company-name { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
.inv-company-sub { font-size: 11px; color: #333; line-height: 1.5; }
.inv-header-right { width: 50%; vertical-align: top; text-align: right; }
.inv-bill-box { display: inline-block; text-align: left; border: 1px solid #000; padding: 7px 10px; min-width: 190px; }
.inv-bill-title { font-size: 11px; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
.inv-bill-row { font-size: 11px; line-height: 1.7; }
.inv-bill-row strong { display: inline-block; min-width: 70px; }
.inv-title { text-align: center; font-size: 18px; font-weight: bold; letter-spacing: 3px; margin: 10px 0 6px; text-transform: uppercase; }
.inv-meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.inv-meta-table th { background: #000; color: #fff; border: 1px solid #000; padding: 5px 8px; text-align: center; font-size: 11px; }
.inv-meta-table td { border: 1px solid #000; padding: 5px 8px; text-align: center; font-size: 11px; }
.inv-items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.inv-items-table th { background: #000; color: #fff; border: 1px solid #000; padding: 5px 7px; font-size: 11px; }
.inv-items-table td { border: 1px solid #000; padding: 5px 7px; font-size: 11px; vertical-align: top; }
.inv-items-table .inv-item-row:nth-child(even) td { background: #f7f7f7; }
.col-code { width: 9%; }
.col-desc { width: 38%; }
.col-qty { width: 11%; }
.col-price { width: 20%; }
.col-total { width: 22%; }
.inv-payment-section { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.inv-pay-left { width: 55%; vertical-align: top; padding-right: 8px; }
.inv-pay-right { width: 45%; vertical-align: top; }
.inv-pay-modes { width: 100%; border-collapse: collapse; }
.inv-pay-modes td { border: 1px solid #000; padding: 5px 8px; font-size: 11px; }
.inv-pay-modes .text-right { text-align: right; font-weight: bold; }
.inv-due-row td { background: #f0f0f0; }
.inv-totals-box { width: 100%; border-collapse: collapse; border: 2px solid #000; }
.inv-totals-box td { border: 1px solid #000; padding: 5px 10px; font-size: 12px; }
.inv-totals-box .text-right { text-align: right; font-weight: bold; }
.inv-grand-row td { background: #000 !important; color: #fff; font-weight: bold; font-size: 13px; }
.inv-notes { border: 1px solid #ccc; padding: 5px 8px; font-size: 11px; margin-bottom: 8px; }
.inv-footer-divider { border-top: 1px solid #000; margin: 8px 0 6px; }
.inv-footer-table { width: 100%; border-collapse: collapse; }
.inv-footer-table td { vertical-align: top; font-size: 10px; color: #333; padding: 0 4px; }
.inv-footer-left { width: 38%; }
.inv-footer-center { width: 24%; text-align: center; }
.inv-footer-right { width: 38%; text-align: right; }
.inv-delivered-stamp { display: inline-block; border: 3px solid #000; border-radius: 4px; padding: 5px 12px; font-size: 14px; font-weight: bold; letter-spacing: 3px; color: #000; opacity: 0.2; transform: rotate(-10deg); margin-top: 2px; }
</style>
