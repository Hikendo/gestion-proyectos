import { useServiceRequest } from './useServiceRequest';

type AsyncServiceMethod = (...args: any[]) => Promise<any>;
type ServiceMap = Record<string, AsyncServiceMethod>;

export function createServiceComposable<TService extends ServiceMap, TField extends string>(
    service: TService,
    fields: readonly TField[] = [],
) {
    return function useGeneratedService() {
        const state = useServiceRequest(fields);

        async function call<TKey extends keyof TService>(
            method: TKey,
            ...args: Parameters<TService[TKey]>
        ): Promise<Awaited<ReturnType<TService[TKey]>> | null> {
            const handler = service[method];

            // Validación más detallada
            if (!handler) {
                console.error(`Method "${String(method)}" does not exist in service`);
                return null;
            }

            if (typeof handler !== 'function') {
                console.error(`Method "${String(method)}" is not a function, got: ${typeof handler}`);
                return null;
            }

            try {
                return await state.execute(() => handler(...args));
            } catch (error) {
                console.error(`Error calling method "${String(method)}":`, error);
                return null;
            }
        }

        return {
            ...state,
            service,
            call,
        };
    };
}
