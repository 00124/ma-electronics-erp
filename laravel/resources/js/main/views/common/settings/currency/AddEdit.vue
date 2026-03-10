<template>
    <a-modal
        :open="visible"
        :closable="false"
        :centered="true"
        :title="pageTitle"
        @ok="onSubmit"
    >
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item
                        :label="$t('currency.name')"
                        name="name"
                        :help="rules.name ? rules.name.message : null"
                        :validateStatus="rules.name ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.name"
                            :placeholder="
                                $t('common.placeholder_default_text', [
                                    $t('currency.name'),
                                ])
                            "
                        />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item
                        :label="$t('currency.symbol')"
                        name="symbol"
                        :help="rules.symbol ? rules.symbol.message : null"
                        :validateStatus="rules.symbol ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.symbol"
                            :placeholder="
                                $t('common.placeholder_default_text', [
                                    $t('currency.symbol'),
                                ])
                            "
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item
                        :label="$t('currency.position')"
                        name="position"
                        :help="rules.position ? rules.position.message : null"
                        :validateStatus="rules.position ? 'error' : null"
                    >
                        <a-radio-group v-model:value="formData.position">
                            <a-radio value="front">
                                {{ $t("currency.front") }}
                                ({{ $t("currency.front_position_example") }})
                            </a-radio>
                            <a-radio value="behind">
                                {{ $t("currency.behind") }}
                                ({{ $t("currency.behind_position_example") }})
                            </a-radio>
                        </a-radio-group>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item
                        :label="$t('currency.code')"
                        name="code"
                        :help="rules.code ? rules.code.message : null"
                        :validateStatus="rules.code ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.code"
                            :placeholder="
                                $t('common.placeholder_default_text', [
                                    $t('currency.code'),
                                ])
                            "
                        />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('currency.decimal_separator')"
                        name="decimal_separator"
                        :help="
                            rules.decimal_separator
                                ? rules.decimal_separator.message
                                : null
                        "
                        :validateStatus="
                            rules.decimal_separator ? 'error' : null
                        "
                    >
                        <a-select
                            v-model:value="formData.decimal_separator"
                            :placeholder="
                                $t('common.select_default_text', [
                                    $t('currency.decimal_separator'),
                                ])
                            "
                        >
                            <a-select-option key="comma" value="comma">
                                {{ $t(`currency.comma`) }}
                            </a-select-option>

                            <a-select-option key="dot" value="dot">
                                {{ $t(`currency.dot`) }}
                            </a-select-option>

                            <a-select-option key="space" value="space">
                                {{ $t(`currency.space`) }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('currency.thousand_separator')"
                        name="thousand_separator"
                        :help="
                            rules.thousand_separator
                                ? rules.thousand_separator.message
                                : null
                        "
                        :validateStatus="
                            rules.thousand_separator ? 'error' : null
                        "
                    >
                        <a-select
                            v-model:value="formData.thousand_separator"
                            :placeholder="
                                $t('common.placeholder_default_text', [
                                    $t('currency.thousand_separator'),
                                ])
                            "
                        >
                            <a-select-option key="comma" value="comma">
                                {{ $t(`currency.comma`) }}
                            </a-select-option>
                            <a-select-option key="dot" value="dot">
                                {{ $t(`currency.dot`) }}
                            </a-select-option>

                            <a-select-option key="space" value="space">
                                {{ $t(`currency.space`) }}
                            </a-select-option>
                            <a-select-option key="none" value="none">
                                {{ $t(`currency.none`) }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('currency.remove_decimal_with_zero')"
                        name="remove_decimal_with_zero"
                        :help="
                            rules.remove_decimal_with_zero
                                ? rules.remove_decimal_with_zero.message
                                : null
                        "
                        :validateStatus="
                            rules.remove_decimal_with_zero ? 'error' : null
                        "
                    >
                        <a-radio-group
                            v-model:value="formData.remove_decimal_with_zero"
                            button-style="solid"
                            size="small"
                        >
                            <a-radio-button :value="1">
                                {{ $t("common.yes") }}
                            </a-radio-button>
                            <a-radio-button :value="0">
                                {{ $t("common.no") }}
                            </a-radio-button>
                        </a-radio-group>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('currency.currency_symbol_spacing')"
                        name="space_between_price_and_price_symbol"
                        :help="
                            rules.space_between_price_and_price_symbol
                                ? rules.space_between_price_and_price_symbol
                                      .message
                                : null
                        "
                        :validateStatus="
                            rules.space_between_price_and_price_symbol
                                ? 'error'
                                : null
                        "
                    >
                        <a-radio-group
                            v-model:value="
                                formData.space_between_price_and_price_symbol
                            "
                            button-style="solid"
                            size="small"
                        >
                            <a-radio-button :value="1">
                                {{ $t("common.yes") }}
                            </a-radio-button>
                            <a-radio-button :value="0">
                                {{ $t("common.no") }}
                            </a-radio-button>
                        </a-radio-group>
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>
        <template #footer>
            <a-button
                key="submit"
                type="primary"
                :loading="loading"
                @click="onSubmit"
            >
                <template #icon>
                    <SaveOutlined />
                </template>
                {{
                    addEditType == "add"
                        ? $t("common.create")
                        : $t("common.update")
                }}
            </a-button>
            <a-button key="back" @click="onClose">
                {{ $t("common.cancel") }}
            </a-button>
        </template>
    </a-modal>
</template>
<script>
import { defineComponent } from "vue";
import {
    PlusOutlined,
    LoadingOutlined,
    SaveOutlined,
} from "@ant-design/icons-vue";
import { useStore } from "vuex";
import apiAdmin from "../../../../../common/composable/apiAdmin";
import common from "../../../../../common/composable/common";

export default defineComponent({
    props: [
        "formData",
        "data",
        "visible",
        "url",
        "addEditType",
        "pageTitle",
        "successMessage",
    ],
    emits: ["addEditSuccess"],
    components: {
        PlusOutlined,
        LoadingOutlined,
        SaveOutlined,
    },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const { appSetting } = common();
        const store = useStore();

        const onSubmit = () => {
            addEditRequestAdmin({
                url: props.url,
                data: props.formData,
                successMessage: props.successMessage,
                success: (res) => {
                    // If changed currency is company currency
                    if (
                        appSetting.value.currency &&
                        appSetting.value.currency.xid == res.xid
                    ) {
                        store.dispatch("auth/updateApp");
                    }

                    emit("addEditSuccess", res.xid);
                },
            });
        };

        const onClose = () => {
            rules.value = {};
            emit("closed");
        };

        return {
            loading,
            rules,
            onClose,
            onSubmit,

            drawerWidth: window.innerWidth <= 991 ? "90%" : "45%",
        };
    },
});
</script>
