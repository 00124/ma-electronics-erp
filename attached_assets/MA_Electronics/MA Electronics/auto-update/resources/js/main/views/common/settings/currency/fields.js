import { useI18n } from "vue-i18n";

const fields = (panelType) => {
    const url = panelType == 'admin' ?
        "currencies?fields=id,xid,name,symbol,position,code,decimal_separator,thousand_separator,remove_decimal_with_zero,space_between_price_and_price_symbol" :
        "superadmin/currencies?fields=id,xid,name,symbol,position,code,decimal_separator,thousand_separator,remove_decimal_with_zero,space_between_price_and_price_symbol";
    const addEditUrl = panelType == 'admin' ? "currencies" : "superadmin/currencies";
    const { t } = useI18n();

    const initData = {
        name: "",
        symbol: "",
        position: "",
        code: "",
        decimal_separator: "dot",
        thousand_separator: "comma",
        remove_decimal_with_zero: 1,
        space_between_price_and_price_symbol: 0

    };

    const columns = [{
            title: t("currency.name"),
            dataIndex: "name",
            sorter: true
        },
        {
            title: t("currency.symbol"),
            dataIndex: "symbol",
            sorter: true
        },
        {
            title: t("currency.position"),
            dataIndex: "position",
            sorter: true
        },
        {
            title: t("currency.code"),
            dataIndex: "code",
            sorter: true
        },
        {
            title: t("currency.decimal_separator"),
            dataIndex: "decimal_separator",
            sorter: true
        },
        {
            title: t("currency.thousand_separator"),
            dataIndex: "thousand_separator",
            sorter: true
        },
        {
            title: t("currency.remove_decimal_with_zero"),
            dataIndex: "remove_decimal_with_zero",
            sorter: true
        },
        {
            title: t("common.action"),
            dataIndex: "action",
        },
    ];

    const filterableColumns = [{
        key: "name",
        value: t("common.name")
    }, ];

    return {
        url,
        addEditUrl,
        initData,
        columns,
        filterableColumns
    }
}

export default fields;