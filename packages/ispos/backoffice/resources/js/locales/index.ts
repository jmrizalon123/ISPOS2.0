import en from './en';
import fil from './fil';
import zhCN from './zh-CN';

export const supportedLocales = ['en', 'zh-CN', 'fil'] as const;

export type AppLocale = (typeof supportedLocales)[number];

export const messages = {
    en,
    'zh-CN': zhCN,
    fil,
};

export default messages;
