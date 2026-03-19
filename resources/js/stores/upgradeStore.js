import { defineStore } from 'pinia';

export const useUpgradeStore = defineStore('upgrade', {
    state: () => ({
        isOpen: false,
        feature: '',
        requiredPlan: 'pro',
        message: '',
    }),
    actions: {
        open(feature = '', requiredPlan = 'pro', message = '') {
            this.feature = feature;
            this.requiredPlan = requiredPlan;
            this.message = message;
            this.isOpen = true;
        },
        close() {
            this.isOpen = false;
        }
    }
});
