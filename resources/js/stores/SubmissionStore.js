import {defineStore} from 'pinia';

let model = window.model;

// You can name the return value of `defineStore()` anything you want,
// but it's best to use the name of the store and surround it with `use`
// and `Store` (e.g. `useUserStore`, `useCartStore`, `useProductStore`)
// the first argument is a unique id of the store across your application
export const useSubmissionStore = defineStore('submission', {
    state() {
        return {
            submission: model.submission,
            form: model.form,
            can_process_submissions: model.can_process_submissions,
            is_workflow_exists: model.is_workflow_exists,
            current_process: model.current_process,
            statuses: model.statuses,
            processes: model.processes,
        }
    },
    getters: {
        canProcessSubmission: function (state) {
            return state.can_process_submissions &&
                state.is_workflow_exists &&
                state.current_process.is_processor &&
                state.current_process.proceed_next_process &&
                !state.current_process.is_revert_submitter &&
                !state.current_process.is_end_process;
        },
    },
})
