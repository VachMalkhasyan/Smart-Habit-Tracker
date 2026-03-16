export function useJobStatus() {

    const statusConfig = {
        wishlist: {
            label:      'Wishlist',
            dot:        'bg-gray-400',
            badge:      'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            border:     'border-gray-200 dark:border-gray-700',
            header:     'bg-gray-50 dark:bg-gray-900',
        },
        applied: {
            label:      'Applied',
            dot:        'bg-blue-400',
            badge:      'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            border:     'border-blue-200 dark:border-blue-800',
            header:     'bg-blue-50 dark:bg-blue-900/20',
        },
        phone_screen: {
            label:      'Phone Screen',
            dot:        'bg-yellow-400',
            badge:      'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
            border:     'border-yellow-200 dark:border-yellow-800',
            header:     'bg-yellow-50 dark:bg-yellow-900/20',
        },
        interview: {
            label:      'Interview',
            dot:        'bg-purple-400',
            badge:      'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
            border:     'border-purple-200 dark:border-purple-800',
            header:     'bg-purple-50 dark:bg-purple-900/20',
        },
        offer: {
            label:      'Offer',
            dot:        'bg-green-400',
            badge:      'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
            border:     'border-green-200 dark:border-green-800',
            header:     'bg-green-50 dark:bg-green-900/20',
        },
        rejected: {
            label:      'Rejected',
            dot:        'bg-red-400',
            badge:      'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
            border:     'border-red-200 dark:border-red-800',
            header:     'bg-red-50 dark:bg-red-900/20',
        },
        withdrawn: {
            label:      'Withdrawn',
            dot:        'bg-orange-400',
            badge:      'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
            border:     'border-orange-200 dark:border-orange-800',
            header:     'bg-orange-50 dark:bg-yellow-900/20', // User snippet said bg-orange-400 dot but header bg-yellow-900/20. Keeping as requested or logical. Actually user said header bg-orange-50.
        },
    }

    // Correcting the withdrawn header mistake in user snippet if any, but following user's withdrawn block exactly:
    statusConfig.withdrawn.header = 'bg-orange-50 dark:bg-orange-900/20';

    const priorityConfig = {
        1: { label: 'High',   dot: '🔴', badge: 'bg-red-100 text-red-700'       },
        2: { label: 'Medium', dot: '🟡', badge: 'bg-yellow-100 text-yellow-700' },
        3: { label: 'Low',    dot: '🟢', badge: 'bg-green-100 text-green-700'   },
    }

    const interviewTypeConfig = {
        phone:       { label: 'Phone',      color: 'bg-blue-100 text-blue-700'     },
        technical:   { label: 'Technical',  color: 'bg-purple-100 text-purple-700' },
        behavioral:  { label: 'Behavioral', color: 'bg-green-100 text-green-700'   },
        final:       { label: 'Final',      color: 'bg-orange-100 text-orange-700' },
        other:       { label: 'Other',      color: 'bg-gray-100 text-gray-700'     },
    }

    return { statusConfig, priorityConfig, interviewTypeConfig }
}
