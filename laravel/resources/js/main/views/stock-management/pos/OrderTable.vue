import { ref, defineComponent } from "vue"; import { PrinterOutlined } from
"@ant-design/icons-vue"; import common from
"../../../../common/composable/common"; import { notification } from
"ant-design-vue"; import { useI18n } from "vue-i18n"; const sendInvoiceMail =
async (xid, lang) => { isSending.value = true; try { await
axiosAdmin.get(`send-mail/${xid}/${lang}`); notification.success({ message:
t("common.success"), description: t("common.mail_sent_successfully"), duration:
4.5 }); } catch (error) { notification.error({ message: t("common.error"),
description: t("common.failed_to_send_mail"), duration: 4.5 }); } finally {
isSending.value = false; } };
