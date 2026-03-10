<template>
    <div class="product-pos" v-if="product && product.xid">
        <div class="product-pos-top">
            <a href="javascript:void(0)">
                <span
                    v-if="product.product_type == 'service'"
                    class="quantity-box"
                    to="#"
                >
                    {{ $t("product.service") }}
                </span>
                <span v-else class="quantity-box" to="#">
                    {{ product.stock_quantity }} {{ product.unit.short_name }}
                </span>

                <img :src="product.image_url" class="img-fit" />
            </a>
        </div>
        <div class="product-pos-bottom">
            <div>
                <h5 class="product-title">
                    {{ product.name }}
                </h5>
            </div>
            <div class="product-details">
                <div class="product-details-row">
                    <span class="product-subtotal">{{
                        formatAmountCurrency(product.subtotal)
                    }}</span>
                    <span v-if="isAdded" class="added-tag">{{
                        $t("product.added")
                    }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import common from "../../composable/common";

export default {
    props: ["product", "isAdded"],
    setup(props) {
        const { formatAmountCurrency } = common();

        return {
            formatAmountCurrency,
        };
    },
};
</script>

<style lang="less">
.product-pos {
    background: #fff;
    border: 3px solid #e8e8e8;
    border-radius: 9px;
    margin-top: 15px;
    position: relative;
}

.product-pos-top {
    display: flex;
    justify-content: center;
    width: 100%;

    a {
        text-align: "center";
    }

    .quantity-box {
        position: absolute;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 15px;
        top: 1px;
        background-color: #fff;
        padding: 0 6px;
        left: 1px;
        left: 20px;
        top: 30px;
    }

    img {
        height: 180px;
        max-width: 100%;
        width: 100%;
        -o-object-fit: cover;
        object-fit: cover;
        padding: 15px;
    }
}

.product-pos-bottom {
    padding-left: 15px;
    padding-right: 15px;
    padding-bottom: 15px;

    .product-category {
        font-weight: 500;
        font-size: 12px;
        color: #9ca3af;
    }

    .product-title {
        font-weight: 400;
        font-size: 14px;
        height: 40px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .product-details {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-top: 15px;

        .product-details-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .product-subtotal {
            font-weight: 600;
            font-size: 18px;
        }
    }
}

.added-tag {
    position: absolute;
    bottom: 18px;
    right: 14px;
    background: #52c41a;
    color: #fff;
    padding: 2px 10px;
    border-radius: 5px;
    font-size: 12px;
    z-index: 2;
}
</style>
