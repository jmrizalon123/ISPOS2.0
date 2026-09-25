import appearanceUi from './fil/appearanceUi';
import auth from './fil/auth';
import cards from './fil/cards';
import columns from './fil/columns';
import dashboard from './fil/dashboard';
import fields from './fil/fields';
import filters from './fil/filters';
import forms from './fil/forms';
import hints from './fil/hints';
import messages from './fil/messages';
import pages from './fil/pages';
import placeholders from './fil/placeholders';
import profile from './fil/profile';
import shared from './fil/shared';
import stats from './fil/stats';
import stockStatus from './fil/stockStatus';
import roleMembers from './fil/roleMembers';
import rolesPanel from './fil/rolesPanel';
import storeEmployees from './fil/storeEmployees';
import toggles from './fil/toggles';

export default {
    ...shared,
    ...pages,
    fields,
    columns,
    forms,
    filters,
    placeholders,
    hints,
    toggles,
    stats,
    stockStatus,
    auth,
    profile,
    dashboard,
    messages,
    cards,
    appearanceUi,
    roleMembers,
    rolesPanel,
    storeEmployees,
};
