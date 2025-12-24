import { defineStore } from 'pinia'

let id = null;
let name = null;
let properties = [];

if (model) {
    id = model.id
    name = model.name;

    if (model.prepare_fields) {
        properties = model.prepare_fields;
    }
}

let submissionId = null;
if (formSubmissionId) {
    submissionId = formSubmissionId;
}

// You can name the return value of `defineStore()` anything you want,
// but it's best to use the name of the store and surround it with `use`
// and `Store` (e.g. `useUserStore`, `useCartStore`, `useProductStore`)
// the first argument is a unique id of the store across your application
export const useFormSubmissionStore = defineStore('form-submission', {
    state() {
        return {
            id: id,
            name: name,
            properties: properties,
            submissionId: submissionId,
            errors: [],
            error_message: null,
        }
    }
})
