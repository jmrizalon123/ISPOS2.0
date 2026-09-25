export type PermissionModuleKey = 'general' | 'pos';

export type PermissionModule = {
    key: PermissionModuleKey;
    labelKey: string;
    descriptionKey: string;
    prefixes: string[];
};

/** POS-related permission prefixes grouped under the POS module tab. */
export const POS_PERMISSION_PREFIXES = ['pos.', 'kds.'] as const;

export const PERMISSION_MODULES: PermissionModule[] = [
    {
        key: 'general',
        labelKey: 'rolesPanel.modules.general',
        descriptionKey: 'rolesPanel.modules.generalDescription',
        prefixes: [],
    },
    {
        key: 'pos',
        labelKey: 'rolesPanel.modules.pos',
        descriptionKey: 'rolesPanel.modules.posDescription',
        prefixes: [...POS_PERMISSION_PREFIXES],
    },
];

export function permissionModuleKey(permission: string): PermissionModuleKey {
    if (POS_PERMISSION_PREFIXES.some((prefix) => permission.startsWith(prefix))) {
        return 'pos';
    }

    return 'general';
}

export function permissionsForModule(permissions: string[], module: PermissionModuleKey): string[] {
    return permissions.filter((permission) => permissionModuleKey(permission) === module);
}

export function selectedCountForModule(permissions: string[], selected: string[], module: PermissionModuleKey): number {
    return permissionsForModule(permissions, module).filter((permission) => selected.includes(permission)).length;
}
