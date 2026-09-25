import { computed, type ComputedRef, type MaybeRefOrGetter, toValue } from 'vue';

export type FormTabNestedArrayRule = {
    key: string;
    requiredKeys: string[];
};

export type FormTabArrayRule = {
    arrayField: string;
    requiredKeys: string[];
    nested?: FormTabNestedArrayRule;
};

export type FormTabErrorConfig = {
    key: string;
    requiredFields?: string[];
    errorPrefixes?: string[];
    arrayRules?: FormTabArrayRule[];
};

function isEmptyValue(value: unknown): boolean {
    if (value === null || value === undefined) {
        return true;
    }

    if (typeof value === 'string') {
        return value.trim() === '';
    }

    if (typeof value === 'number') {
        return Number.isNaN(value);
    }

    if (Array.isArray(value)) {
        return value.length === 0;
    }

    return false;
}

function getByPath(data: Record<string, unknown>, path: string): unknown {
    return path.split('.').reduce<unknown>((current, segment) => {
        if (current === null || current === undefined || typeof current !== 'object') {
            return undefined;
        }

        return (current as Record<string, unknown>)[segment];
    }, data);
}

function errorMatchesTab(errorKey: string, config: FormTabErrorConfig): boolean {
    if (config.requiredFields?.includes(errorKey)) {
        return true;
    }

    return (config.errorPrefixes ?? []).some(
        (prefix) => errorKey === prefix || errorKey.startsWith(`${prefix}.`),
    );
}

function collectArrayIssues(data: Record<string, unknown>, rule: FormTabArrayRule): Set<string> {
    const issues = new Set<string>();
    const rows = getByPath(data, rule.arrayField);

    if (!Array.isArray(rows) || rows.length === 0) {
        return issues;
    }

    rows.forEach((row, rowIndex) => {
        if (typeof row !== 'object' || row === null) {
            return;
        }

        const record = row as Record<string, unknown>;

        for (const key of rule.requiredKeys) {
            if (isEmptyValue(record[key])) {
                issues.add(`${rule.arrayField}.${rowIndex}.${key}`);
            }
        }

        if (!rule.nested) {
            return;
        }

        const nestedRows = record[rule.nested.key];
        if (!Array.isArray(nestedRows) || nestedRows.length === 0) {
            return;
        }

        nestedRows.forEach((nestedRow, nestedIndex) => {
            if (typeof nestedRow !== 'object' || nestedRow === null) {
                return;
            }

            const nestedRecord = nestedRow as Record<string, unknown>;
            for (const key of rule.nested!.requiredKeys) {
                if (isEmptyValue(nestedRecord[key])) {
                    issues.add(`${rule.arrayField}.${rowIndex}.${rule.nested!.key}.${nestedIndex}.${key}`);
                }
            }
        });
    });

    return issues;
}

function countTabIssues(
    config: FormTabErrorConfig,
    data: Record<string, unknown>,
    errors: Record<string, string>,
): number {
    const issues = new Set<string>();

    for (const field of config.requiredFields ?? []) {
        if (isEmptyValue(getByPath(data, field))) {
            issues.add(field);
        }
    }

    for (const rule of config.arrayRules ?? []) {
        for (const issue of collectArrayIssues(data, rule)) {
            issues.add(issue);
        }
    }

    for (const errorKey of Object.keys(errors)) {
        if (errorMatchesTab(errorKey, config)) {
            issues.add(errorKey);
        }
    }

    return issues.size;
}

export function useFormTabErrors(
    formData: MaybeRefOrGetter<object>,
    formErrors: MaybeRefOrGetter<Record<string, string>>,
    configs: MaybeRefOrGetter<FormTabErrorConfig[]>,
): ComputedRef<Record<string, number>> {
    return computed(() => {
        const data = toValue(formData) as Record<string, unknown>;
        const errors = toValue(formErrors);
        const tabConfigs = toValue(configs);
        const result: Record<string, number> = {};

        for (const config of tabConfigs) {
            const count = countTabIssues(config, data, errors);
            if (count > 0) {
                result[config.key] = count;
            }
        }

        return result;
    });
}
