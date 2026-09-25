export function formatRecordCount(total: number, singular: string, plural?: string): string {
    const word = total === 1 ? singular : (plural ?? `${singular}s`);

    return `${total.toLocaleString()} ${word}`;
}
