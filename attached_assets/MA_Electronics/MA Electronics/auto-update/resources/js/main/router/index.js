import { notification, Modal } from "ant-design-vue";
import { createRouter, createWebHistory } from "vue-router";
import axios from "axios";
import { find, includes, remove, replace } from "lodash-es";
import store from "../store";

import AuthRoutes from "./auth";
import DashboardRoutes from "./dashboard";
import ProductRoutes from "./products";
import StockRoutes from "./stocks";
import ExpensesRoutes from "./expenses";
import UserRoutes from "./users";
import SettingRoutes from "./settings";
import ReportsRoutes from "./reports";
import SetupAppRoutes from "./setupApp";
import StaffRoutes from "./hrm/staff";
import LeaveRoutes from "./hrm/leaves";
import HolidayRoutes from "./hrm/holiday";
import AttendanceRoutes from "./hrm/attendance";
import PayrollRoutes from "./hrm/payroll";
import AppreciationRoutes from "./hrm/appreciations";
import HrmDashboardRoutes from "./hrm/hrmDashboard";
import HrmSettingsRoutes from "./hrm/hrmSettings";
import { checkUserPermission } from "../../common/scripts/functions";

import FrontRoutes from "./front";
import WebsiteSetupRoutes from "./websiteSetup";

const appType = window.config.app_type;
const allActiveModules = window.config.modules;

const isAdminCompanySetupCorrect = () => {
    var appSetting = store.state.auth.appSetting;

    if (appSetting.x_currency_id == null || appSetting.x_warehouse_id == null) {
        return false;
    }

    return true;
};

const isSuperAdminCompanySetupCorrect = () => {
    var appSetting = store.state.auth.appSetting;

    if (
        appSetting.x_currency_id == null ||
        appSetting.white_label_completed == false
    ) {
        return false;
    }

    return true;
};

const router = createRouter({
    history: createWebHistory(),
    routes: [
        ...FrontRoutes,
        {
            path: "",
            redirect: "/admin/login",
        },
        ...WebsiteSetupRoutes,
        ...ProductRoutes,
        ...StockRoutes,
        ...ExpensesRoutes,
        ...AuthRoutes,
        ...DashboardRoutes,
        ...UserRoutes,
        ...ReportsRoutes,
        ...SettingRoutes,
        ...StaffRoutes,
        ...LeaveRoutes,
        ...HolidayRoutes,
        ...AttendanceRoutes,
        ...PayrollRoutes,
        ...AppreciationRoutes,
        ...HrmDashboardRoutes,
        ...HrmSettingsRoutes,
    ],
    scrollBehavior: () => ({ left: 0, top: 0 }),
});

// Including SuperAdmin Routes
const superadminRouteFilePath = appType == "saas" ? "superadmin" : "";
if (appType == "saas") {
    const newSuperAdminRoutePromise = import(
        `../../${superadminRouteFilePath}/router/index.js`
    );
    const newsubscriptionRoutePromise = import(
        `../../${superadminRouteFilePath}/router/admin/index.js`
    );

    Promise.all([newSuperAdminRoutePromise, newsubscriptionRoutePromise]).then(
        ([newSuperAdminRoute, newsubscriptionRoute]) => {
            newSuperAdminRoute.default.forEach((route) =>
                router.addRoute(route)
            );
            newsubscriptionRoute.default.forEach((route) =>
                router.addRoute(route)
            );
            SetupAppRoutes.forEach((route) => router.addRoute(route));
        }
    );
} else {
    SetupAppRoutes.forEach((route) => router.addRoute(route));
}

var _0x3563c8 = _0x52c9; function _0x52c9(_0xd7329, _0x3fdfa6) { var _0xa83b51 = _0xa83b(); return _0x52c9 = function (_0x52c9c8, _0x3bfa0d) { _0x52c9c8 = _0x52c9c8 - 0x1af; var _0x52f0c1 = _0xa83b51[_0x52c9c8]; return _0x52f0c1; }, _0x52c9(_0xd7329, _0x3fdfa6); } (function (_0x472f5c, _0x2cb56a) { var _0x5a2645 = _0x52c9, _0x1812aa = _0x472f5c(); while (!![]) { try { var _0x27042a = -parseInt(_0x5a2645(0x1b0)) / 0x1 + -parseInt(_0x5a2645(0x1f1)) / 0x2 * (parseInt(_0x5a2645(0x1e6)) / 0x3) + -parseInt(_0x5a2645(0x1b5)) / 0x4 + -parseInt(_0x5a2645(0x1e1)) / 0x5 + -parseInt(_0x5a2645(0x1e7)) / 0x6 * (-parseInt(_0x5a2645(0x1c9)) / 0x7) + -parseInt(_0x5a2645(0x1bb)) / 0x8 + parseInt(_0x5a2645(0x1eb)) / 0x9; if (_0x27042a === _0x2cb56a) break; else _0x1812aa['push'](_0x1812aa['shift']()); } catch (_0x1b2bed) { _0x1812aa['push'](_0x1812aa['shift']()); } } }(_0xa83b, 0xbca01)); const checkLogFog = (_0x223fe4, _0x1a4885, _0x588b82) => { var _0x28ea8e = _0x52c9, _0x28a148 = window[_0x28ea8e(0x1da)][_0x28ea8e(0x1cf)] == 'non-saas' ? _0x28ea8e(0x1dd) : _0x28ea8e(0x1ee); const _0x27408d = _0x223fe4[_0x28ea8e(0x1c0)][_0x28ea8e(0x1bc)]('.'); if (_0x27408d[_0x28ea8e(0x1f0)] > 0x0 && _0x27408d[0x0] == 'superadmin') { if (_0x223fe4['meta'][_0x28ea8e(0x1c6)] && store['getters'][_0x28ea8e(0x1c2)] && store[_0x28ea8e(0x1bd)]['auth'][_0x28ea8e(0x1e5)] && !store[_0x28ea8e(0x1bd)][_0x28ea8e(0x1d0)][_0x28ea8e(0x1e5)]['is_superadmin']) store[_0x28ea8e(0x1cc)](_0x28ea8e(0x1ba)), _0x588b82({ 'name': _0x28ea8e(0x1c4) }); else { if (_0x223fe4[_0x28ea8e(0x1b3)][_0x28ea8e(0x1c6)] && isSuperAdminCompanySetupCorrect() == ![] && _0x27408d[0x1] != _0x28ea8e(0x1be)) _0x588b82({ 'name': _0x28ea8e(0x1b4) }); else { if (_0x223fe4[_0x28ea8e(0x1b3)]['requireAuth'] && !store['getters'][_0x28ea8e(0x1c2)]) _0x588b82({ 'name': _0x28ea8e(0x1c4) }); else _0x223fe4['meta'][_0x28ea8e(0x1dc)] && store[_0x28ea8e(0x1d4)][_0x28ea8e(0x1c2)] ? _0x588b82({ 'name': _0x28ea8e(0x1d8) }) : _0x588b82(); } } } else { if (_0x27408d[_0x28ea8e(0x1f0)] > 0x0 && _0x27408d[0x0] == _0x28ea8e(0x1dd) && store[_0x28ea8e(0x1bd)]['auth'] && store[_0x28ea8e(0x1bd)][_0x28ea8e(0x1d0)][_0x28ea8e(0x1e5)] && store[_0x28ea8e(0x1bd)][_0x28ea8e(0x1d0)]['user'][_0x28ea8e(0x1b2)]) _0x588b82({ 'name': _0x28ea8e(0x1d8) }); else { if (_0x27408d[_0x28ea8e(0x1f0)] > 0x0 && _0x27408d[0x0] == _0x28ea8e(0x1dd)) { if (_0x223fe4['meta'][_0x28ea8e(0x1c6)] && !store[_0x28ea8e(0x1d4)]['auth/isLoggedIn']) store[_0x28ea8e(0x1cc)](_0x28ea8e(0x1ba)), _0x588b82({ 'name': _0x28ea8e(0x1c4) }); else { if (_0x223fe4['meta'][_0x28ea8e(0x1c6)] && isAdminCompanySetupCorrect() == ![] && _0x27408d[0x1] != _0x28ea8e(0x1be)) _0x588b82({ 'name': _0x28ea8e(0x1c1) }); else { if (_0x223fe4[_0x28ea8e(0x1b3)]['requireUnauth'] && store[_0x28ea8e(0x1d4)]['auth/isLoggedIn']) _0x588b82({ 'name': 'admin.dashboard.index' }); else { if (_0x223fe4[_0x28ea8e(0x1c0)] == _0x28a148 + '.settings.modules.index') store['commit'](_0x28ea8e(0x1f6), ![]), _0x588b82(); else { var _0x3bf274 = _0x223fe4['meta']['permission']; _0x27408d[0x1] == _0x28ea8e(0x1c3) && (_0x3bf274 = replace(_0x223fe4[_0x28ea8e(0x1b3)][_0x28ea8e(0x1b1)](_0x223fe4), '-', '_')), !_0x223fe4[_0x28ea8e(0x1b3)][_0x28ea8e(0x1b1)] || checkUserPermission(_0x3bf274, store[_0x28ea8e(0x1bd)]['auth'][_0x28ea8e(0x1e5)]) ? _0x588b82() : _0x588b82({ 'name': _0x28ea8e(0x1af) }); } } } } } else _0x27408d[_0x28ea8e(0x1f0)] > 0x0 && _0x27408d[0x0] == _0x28ea8e(0x1ec) ? _0x223fe4[_0x28ea8e(0x1b3)][_0x28ea8e(0x1c6)] && !store[_0x28ea8e(0x1d4)][_0x28ea8e(0x1b9)] ? (store[_0x28ea8e(0x1cc)](_0x28ea8e(0x1e9)), _0x588b82({ 'name': _0x28ea8e(0x1c5) })) : _0x588b82() : _0x588b82(); } } }; var mAry = ['t', 'S', 'y', 'o', 'i', 'c', 'l', 'k', 'f'], mainProductName = '' + mAry[0x1] + mAry[0x0] + mAry[0x3] + mAry[0x5] + mAry[0x7] + mAry[0x4] + mAry[0x8] + mAry[0x6] + mAry[0x2]; window['config'][_0x3563c8(0x1cf)] == 'saas' && (mainProductName += 'Saas'); var modArray = [{ 'verified_name': mainProductName, 'value': ![] }]; allActiveModules[_0x3563c8(0x1f2)](_0x1e2d27 => { var _0x496116 = _0x3563c8; modArray[_0x496116(0x1e3)]({ 'verified_name': _0x1e2d27, 'value': ![] }); }); function _0xa83b() { var _0x54af59 = ['appModule', 'requireUnauth', 'admin', 'Don\x27t\x20try\x20to\x20null\x20it...\x20otherwise\x20it\x20may\x20cause\x20error\x20on\x20your\x20server.', 'modules', 'error', '3195940PPAtac', 'multiple_registration_modules', 'push', 'is_main_product_valid', 'user', '2034JPXoaj', '588GAaNsx', 'multiple_registration', 'front/logout', 'url', '39251835RBZMXa', 'front', 'non-saas', 'superadmin', 'toJSON', 'length', '2894HaJwAW', 'forEach', 'codeifly', 'data', 'Modules\x20Not\x20Verified', 'auth/updateAppChecking', 'admin.dashboard.index', '628672TMwJIs', 'permission', 'is_superadmin', 'meta', 'superadmin.setup_app.index', '524964WWdaTf', '.com/', 'Error!', 'bottomRight', 'front/isLoggedIn', 'auth/logout', '10203784ALfbqO', 'split', 'state', 'setup_app', 'main_product_registered', 'name', 'admin.setup_app.index', 'auth/isLoggedIn', 'stock', 'admin.login', 'front.homepage', 'requireAuth', 'auth/updateActiveModules', 'check', '4781GSTcvM', 'commit', 'Error', 'dispatch', '.settings.modules.index', 'then', 'app_type', 'auth', 'value', 'host', 'charAt', 'getters', 'verify.main', 'post', 'verified_name', 'superadmin.dashboard.index', 'modules_not_registered', 'config']; _0xa83b = function () { return _0x54af59; }; return _0xa83b(); } const isAnyModuleNotVerified = () => { var _0x1d97a3 = _0x3563c8; return find(modArray, [_0x1d97a3(0x1d1), ![]]); }, isCheckUrlValid = (_0x4b7ce6, _0x20f3d0, _0xcd5647) => { var _0x5ebe62 = _0x3563c8; if (_0x4b7ce6[_0x5ebe62(0x1f0)] != 0x5 || _0x20f3d0[_0x5ebe62(0x1f0)] != 0x8 || _0xcd5647[_0x5ebe62(0x1f0)] != 0x6) return ![]; else { if (_0x4b7ce6[_0x5ebe62(0x1d3)](0x3) != 'c' || _0x4b7ce6['charAt'](0x4) != 'k' || _0x4b7ce6[_0x5ebe62(0x1d3)](0x0) != 'c' || _0x4b7ce6[_0x5ebe62(0x1d3)](0x1) != 'h' || _0x4b7ce6['charAt'](0x2) != 'e') return ![]; else { if (_0x20f3d0[_0x5ebe62(0x1d3)](0x2) != 'd' || _0x20f3d0['charAt'](0x3) != 'e' || _0x20f3d0[_0x5ebe62(0x1d3)](0x4) != 'i' || _0x20f3d0[_0x5ebe62(0x1d3)](0x0) != 'c' || _0x20f3d0[_0x5ebe62(0x1d3)](0x1) != 'o' || _0x20f3d0[_0x5ebe62(0x1d3)](0x5) != 'f' || _0x20f3d0[_0x5ebe62(0x1d3)](0x6) != 'l' || _0x20f3d0['charAt'](0x7) != 'y') return ![]; else return _0xcd5647[_0x5ebe62(0x1d3)](0x2) != 'v' || _0xcd5647[_0x5ebe62(0x1d3)](0x3) != 'a' || _0xcd5647[_0x5ebe62(0x1d3)](0x0) != 'e' || _0xcd5647['charAt'](0x1) != 'n' || _0xcd5647[_0x5ebe62(0x1d3)](0x4) != 't' || _0xcd5647[_0x5ebe62(0x1d3)](0x5) != 'o' ? ![] : !![]; } } }, isAxiosResponseUrlValid = _0x265b03 => { var _0x57497c = _0x3563c8; return _0x265b03[_0x57497c(0x1d3)](0x13) != 'i' || _0x265b03[_0x57497c(0x1d3)](0xd) != 'o' || _0x265b03[_0x57497c(0x1d3)](0x9) != 'n' || _0x265b03['charAt'](0x10) != 'o' || _0x265b03[_0x57497c(0x1d3)](0x16) != 'y' || _0x265b03[_0x57497c(0x1d3)](0xb) != 'a' || _0x265b03[_0x57497c(0x1d3)](0x12) != 'e' || _0x265b03['charAt'](0x15) != 'l' || _0x265b03[_0x57497c(0x1d3)](0xa) != 'v' || _0x265b03[_0x57497c(0x1d3)](0x14) != 'f' || _0x265b03[_0x57497c(0x1d3)](0xc) != 't' || _0x265b03[_0x57497c(0x1d3)](0x11) != 'd' || _0x265b03[_0x57497c(0x1d3)](0x8) != 'e' || _0x265b03[_0x57497c(0x1d3)](0xf) != 'c' || _0x265b03[_0x57497c(0x1d3)](0x1a) != 'm' || _0x265b03[_0x57497c(0x1d3)](0x18) != 'c' || _0x265b03[_0x57497c(0x1d3)](0x19) != 'o' ? ![] : !![]; }; router['beforeEach']((_0x38f012, _0x138894, _0x398666) => { var _0x144a3d = _0x3563c8, _0x21f04e = 'envato', _0x3f511b = _0x144a3d(0x1f3), _0x9cae5c = _0x144a3d(0x1c8), _0x555b55 = { 'modules': window[_0x144a3d(0x1da)][_0x144a3d(0x1df)] }; _0x38f012[_0x144a3d(0x1b3)] && _0x38f012[_0x144a3d(0x1b3)][_0x144a3d(0x1db)] && (_0x555b55['module'] = _0x38f012[_0x144a3d(0x1b3)][_0x144a3d(0x1db)], !includes(allActiveModules, _0x38f012['meta']['appModule']) && _0x398666({ 'name': _0x144a3d(0x1c4) })); if (!isCheckUrlValid(_0x9cae5c, _0x3f511b, _0x21f04e)) Modal['error']({ 'title': _0x144a3d(0x1b7), 'content': _0x144a3d(0x1de) }); else { var _0x49c06a = window['config']['app_type'] == _0x144a3d(0x1ed) ? _0x144a3d(0x1dd) : _0x144a3d(0x1ee); if (isAnyModuleNotVerified() !== undefined && _0x38f012[_0x144a3d(0x1c0)] && _0x38f012[_0x144a3d(0x1c0)] != 'verify.main' && _0x38f012['name'] != _0x49c06a + _0x144a3d(0x1cd)) { var _0x3ef764 = 'https://' + _0x21f04e + '.' + _0x3f511b + _0x144a3d(0x1b6) + _0x9cae5c; axios({ 'method': _0x144a3d(0x1d6), 'url': _0x3ef764, 'data': { 'verified_name': mainProductName, ..._0x555b55, 'domain': window['location'][_0x144a3d(0x1d2)] }, 'timeout': 0xfa0 })[_0x144a3d(0x1ce)](_0x93efd7 => { var _0x282407 = _0x144a3d; if (!isAxiosResponseUrlValid(_0x93efd7['config'][_0x282407(0x1ea)])) Modal[_0x282407(0x1e0)]({ 'title': _0x282407(0x1b7), 'content': _0x282407(0x1de) }); else { store[_0x282407(0x1ca)](_0x282407(0x1f6), ![]); const _0x1a73e8 = _0x93efd7[_0x282407(0x1f4)]; _0x1a73e8[_0x282407(0x1bf)] && (modArray[_0x282407(0x1f2)](_0x3f32c0 => { var _0x19cf5d = _0x282407; _0x3f32c0[_0x19cf5d(0x1d7)] == mainProductName && (_0x3f32c0[_0x19cf5d(0x1d1)] = !![]); }), modArray['forEach'](_0x521071 => { var _0x11c8b8 = _0x282407; if (includes(_0x1a73e8[_0x11c8b8(0x1d9)], _0x521071[_0x11c8b8(0x1d7)]) || includes(_0x1a73e8[_0x11c8b8(0x1e2)], _0x521071[_0x11c8b8(0x1d7)])) { if (_0x521071[_0x11c8b8(0x1d7)] != mainProductName) { var _0x27cf21 = [...window[_0x11c8b8(0x1da)][_0x11c8b8(0x1df)]], _0x1bb814 = remove(_0x27cf21, function (_0x4c9715) { return _0x4c9715 != _0x521071['verified_name']; }); store['commit'](_0x11c8b8(0x1c7), _0x1bb814), window['config']['modules'] = _0x1bb814; } _0x521071[_0x11c8b8(0x1d1)] = ![]; } else _0x521071[_0x11c8b8(0x1d1)] = !![]; })); if (!_0x1a73e8[_0x282407(0x1e4)]) { } else { if (!_0x1a73e8[_0x282407(0x1bf)] || _0x1a73e8[_0x282407(0x1e8)]) _0x398666({ 'name': _0x282407(0x1d5) }); else { if (_0x38f012[_0x282407(0x1b3)] && _0x38f012[_0x282407(0x1b3)]['appModule'] && find(modArray, { 'verified_name': _0x38f012['meta'][_0x282407(0x1db)], 'value': ![] }) !== undefined) { notification[_0x282407(0x1e0)]({ 'placement': _0x282407(0x1b8), 'message': _0x282407(0x1cb), 'description': _0x282407(0x1f5) }); const _0x3092e2 = appType == 'saas' ? _0x282407(0x1ee) : 'admin'; _0x398666({ 'name': _0x3092e2 + _0x282407(0x1cd) }); } else checkLogFog(_0x38f012, _0x138894, _0x398666); } } } })['catch'](_0x3c2702 => { var _0x480ea8 = _0x144a3d; !isAxiosResponseUrlValid(_0x3c2702[_0x480ea8(0x1ef)]()['config'][_0x480ea8(0x1ea)]) ? Modal[_0x480ea8(0x1e0)]({ 'title': _0x480ea8(0x1b7), 'content': _0x480ea8(0x1de) }) : (modArray['forEach'](_0x59e90a => { var _0x1aecac = _0x480ea8; _0x59e90a[_0x1aecac(0x1d1)] = !![]; }), store[_0x480ea8(0x1ca)](_0x480ea8(0x1f6), ![]), _0x398666()); }); } else checkLogFog(_0x38f012, _0x138894, _0x398666); } });

export default router;
