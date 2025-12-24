import {defineStore} from 'pinia';
import {collect} from "collect.js";

let id = null;
let name = trans.untitled_form;
let type_id = null;
let properties = [];
let webhook_url = null;
let use_current_url = false;
let inputs = {};

if (model) {
    id = model.id
    name = model.name;
    type_id = model.type_id;
    webhook_url = model.webhook_url;

    if (model.use_current_url) {
        if (model.use_current_url === 1) {
            use_current_url = true;
        }

        if (model.use_current_url === 0) {
            use_current_url = false;
        }
    }

    if (model.prepare_fields) {
        properties = model.prepare_fields;
    }

    if (model.prepare_input) {
        inputs = model.prepare_input;
    }
}

// You can name the return value of `defineStore()` anything you want,
// but it's best to use the name of the store and surround it with `use`
// and `Store` (e.g. `useUserStore`, `useCartStore`, `useProductStore`)
// the first argument is a unique id of the store across your application
export const useFormStore = defineStore('form', {
    state() {
        return {
            id: id,
            name: name,
            type_id: type_id,
            properties: properties,
            use_current_url: use_current_url,
            webhook_url: webhook_url,
            errors: [],
            inputs: inputs,
            data: {
                formFields: formFields,
            }
        }
    },
    getters: {
        propertiesDropdown(state) {
            let items = collect();

            let properties = collect(state.properties);
            properties.each((property) => {
                if (
                    property.type === state.data.formFields.text ||
                    property.type === state.data.formFields.email ||
                    property.type === state.data.formFields.textarea ||
                    property.type === state.data.formFields.date ||
                    property.type === state.data.formFields.phone ||
                    property.type === state.data.formFields.number ||
                    property.type === state.data.formFields.url ||
                    property.type === state.data.formFields.hidden ||
                    property.type === state.data.formFields.radio ||
                    property.type === state.data.formFields.checkbox ||
                    property.type === state.data.formFields.select ||
                    property.type === state.data.formFields.file ||
                    property.type === state.data.formFields.currency ||
                    property.type === state.data.formFields.time ||
                    property.type === state.data.formFields.checkbox_group
                ) {
                    items.push({id: property.id, name: property.name});
                }
            })

            return items.all();
        }
    },
    actions: {
        removePropertyRule(id) {
            let properties = collect(this.properties);

            this.properties = properties.each(function (property) {
                if (property.hasOwnProperty('logic')) {
                    if (property.logic.enabled === true) {
                        let groups = collect(property.logic.conditions.group);

                        groups.each((group, index) => {
                            let rules = collect(group.rules);

                            let filtered = rules.reject(function (rule) {
                                return rule.property_id === id;
                            });

                            property.logic.conditions.group[index].rules = filtered.all();
                        })
                    }
                }

                return property;
            });
        },
        findById(id) {
            let properties = collect(this.properties);

            let property = properties.where('id', id).first();

            if (property) {
                return property;
            }

            return false;
        },
        removeInput(id) {
            if (this.inputs.hasOwnProperty(id)) {
                delete this.inputs[id];
            }
        }
    },
})
