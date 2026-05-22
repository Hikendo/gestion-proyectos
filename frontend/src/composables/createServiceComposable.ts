import { useServiceRequest } from './useServiceRequest';

type AsyncServiceMethod = (...args: any[]) => Promise<any>;
type ServiceMap = Record<string, AsyncServiceMethod>;

export function createServiceComposable<TService extends ServiceMap, TField extends string>(
    service: TService,
    fields: readonly TField[] = [],
) {
    return function useGeneratedService() {
        const state = useServiceRequest(fields);

        function call<TKey extends keyof TService>(
            method: TKey,
            ...args: Parameters<TService[TKey]>
        ): Promise<Awaited<ReturnType<TService[TKey]>> | null> {
            const handler = service[method] as (...innerArgs: Parameters<TService[TKey]>) => ReturnType<TService[TKey]>;

            return state.execute(() => handler(...args)) as Promise<Awaited<ReturnType<TService[TKey]>> | null>;
        }

        return {
            ...state,
            service,
            call,
        };
    };
}
