import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePlan() {
    const page = usePage();

    // These props are shared via HandleInertiaRequests.php
    const plan = computed(() => page.props.plan || 'free');
    const limits = computed(() => page.props.plan_limits || {});
    const planName = computed(() => page.props.plan_name || 'Free');

    const isFree = computed(() => plan.value === 'free');
    const isPro = computed(() => plan.value === 'pro' || plan.value === 'max');
    const isMax = computed(() => plan.value === 'max');

    const can = (feature) => {
        return !!limits.value[feature];
    };

    const getLimit = (feature) => {
        return limits.value[feature] ?? 0;
    };

    const hasReached = (feature, current) => {
        const limit = getLimit(feature);
        if (limit === -1) return false;
        return current >= limit;
    };

    return {
        plan,
        limits,
        planName,
        isFree,
        isPro,
        isMax,
        can,
        getLimit,
        hasReached,
    };
}
