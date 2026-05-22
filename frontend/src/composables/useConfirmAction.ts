export interface ConfirmActionOptions {
    message: string;
}

export function useConfirmAction() {
    function confirmAction(options: ConfirmActionOptions): boolean {
        if (typeof window === 'undefined') {
            return false;
        }

        return window.confirm(options.message);
    }

    return {
        confirmAction,
    };
}
