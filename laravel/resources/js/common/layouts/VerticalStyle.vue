<template>
    <Div>
        <section id="components-layout-demo-responsive">
            <!-- Mobile sidebar overlay -->
            <div
                v-if="innerWidth <= 991 && !menuCollapsed"
                class="mobile-sidebar-overlay"
                @click="closeSidebar"
            ></div>

            <a-layout>
                <LeftSidebarBar />

                <a-layout>
                    <MainArea
                        :innerWidth="innerWidth"
                        :collapsed="menuCollapsed"
                        :isRtl="appSetting.rtl"
                    >
                        <TopBar />
                        <MainContentArea>
                            <LicenseDetails v-if="appType == 'saas'" />

                            <a-layout-content>
                                <router-view></router-view>
                            </a-layout-content>

                            <AffixButton
                                v-if="
                                    appSetting.shortcut_menus != 'top' &&
                                    selectedWarehouse &&
                                    selectedWarehouse.name
                                "
                            />
                        </MainContentArea>
                    </MainArea>
                </a-layout>
            </a-layout>
        </section>
    </Div>
</template>

<script>
import { ref, onMounted, onUnmounted } from "vue";
import { useStore } from "vuex";
import TopBar from "./TopBar.vue";
import LeftSidebarBar from "./LeftSidebar.vue";
import { Div, MainArea, MainContentArea } from "./style";
import common from "../composable/common";
import AffixButton from "./AffixButton.vue";
import LicenseDetails from "./LicenseDetails.vue";

export default {
    components: {
        TopBar,
        LeftSidebarBar,
        Div,
        MainArea,
        MainContentArea,
        AffixButton,
        LicenseDetails,
    },
    setup() {
        const { appSetting, menuCollapsed, selectedWarehouse, appType } = common();
        const store = useStore();
        const innerWidth = ref(window.innerWidth);

        const handleResize = () => {
            innerWidth.value = window.innerWidth;
        };

        const closeSidebar = () => {
            store.commit("auth/updateMenuCollapsed", true);
        };

        onMounted(() => {
            window.addEventListener("resize", handleResize);
            if (window.innerWidth <= 991 && !menuCollapsed.value) {
                store.commit("auth/updateMenuCollapsed", true);
            }
        });

        onUnmounted(() => {
            window.removeEventListener("resize", handleResize);
        });

        return {
            appType,
            appSetting,
            menuCollapsed,
            selectedWarehouse,
            innerWidth,
            closeSidebar,
        };
    },
};
</script>

<style>
#components-layout-demo-responsive .logo {
    height: 32px;
    margin: 16px;
    text-align: center;
}

.site-layout-sub-header-background {
    background: #fff;
}

.site-layout-background {
    background: #fff;
}

[data-theme="dark"] .site-layout-sub-header-background {
    background: #141414;
}

.mobile-sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 997;
}
</style>
