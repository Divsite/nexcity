import {collect} from "collect.js";
import dayjs from "dayjs";

let compare = comparisonOperator;
let conditionalOperator = conditionalRuleOperator;

export function conditionMet(property, inputs) {
    let globalOperator = property.logic.conditions.operator;
    let conditions = collect(property.logic.conditions.group);
    let conditionGroup = collect();

    conditions.each((condition) => {
        let groupOperator = condition.operator;
        let rules = collect(condition.rules);
        let conditionRules = collect();

        rules.each((rule) => {
            let field = collect(inputs);

            if (field.has(rule.property_id)) {
                conditionRules.push(ruleMet(rule, inputs));
            }
        });

        conditionGroup.push(checkConditionMet(conditionRules, groupOperator));
    });

    return checkConditionMet(conditionGroup, globalOperator);
}

function checkConditionMet(group, operator) {
    if (operator === conditionalOperator.all) {
        return group.every((item) => item === true);
    }

    if (operator === conditionalOperator.any) {
        return group.some((item) => item === true);
    }

    return false;
}

function ruleMet(rule, value) {
    switch (rule.compare) {
        case (compare.is_present) :
            return isPresent(rule, value);
        case (compare.is_blank) :
            return isBlank(rule, value);
        case (compare.is) :
            return is(rule, value);
        case (compare.is_not) :
            return isNot(rule, value);
        case (compare.contains) :
            return contains(rule, value);
        case (compare.does_not_contains) :
            return doesNotContains(rule, value);
        case (compare.starts_with) :
            return startsWith(rule, value);
        case (compare.ends_with) :
            return endsWith(rule, value);
        case (compare.is_before) :
            return isBefore(rule, value);
        case (compare.is_after) :
            return isAfter(rule, value);
        case (compare.is_checked) :
            return isChecked(rule, value);
        case (compare.is_not_checked) :
            return isNotChecked(rule, value);
        case (compare.has_file_selected) :
            return hasFileSelected(rule, value);
        case (compare.has_no_file_selected) :
            return hasNoFileSelected(rule, value);
    }
    return false;
}

function isPresent(rule, value) {
    return !!value[rule.property_id];
}

function isBlank(rule, value) {
    return !value[rule.property_id];
}

function is(rule, value) {
    return rule.value === value[rule.property_id];
}

function isNot(rule, value) {
    return rule.value !== value[rule.property_id];
}

function contains(rule, value) {
    return value[rule.property_id] ? value[rule.property_id].includes(rule.value) : false;
}

function doesNotContains(rule, value) {
    return value[rule.property_id] ? !value[rule.property_id].includes(rule.value) : false;
}

function startsWith(rule, value) {
    return value[rule.property_id] ? value[rule.property_id].startsWith(rule.value) : false;
}

function endsWith(rule, value) {
    return value[rule.property_id] ? value[rule.property_id].endsWith(rule.value) : false;
}

function isBefore(rule, value) {
    return value[rule.property_id] ? dayjs(value[rule.property_id]).isBefore(dayjs(rule.value), "date") : false;
}

function isAfter(rule, value) {
    return value[rule.property_id] ? dayjs(value[rule.property_id]).isAfter(dayjs(rule.value), "date") : false;
}

function isChecked(rule, value) {
    if (!value.hasOwnProperty(rule.property_id)) {
        return false;
    }

    let formData = value[rule.property_id];

    // Checkbox Group
    if (Array.isArray(formData)) {
        return formData.includes(rule.value);
    }

    // Single checkbox
    return formData === true;
}

function isNotChecked(rule, value) {
    if (!value.hasOwnProperty(rule.property_id)) {
        return false;
    }

    let formData = value[rule.property_id];

    // Checkbox Group
    if (Array.isArray(formData)) {
        return !formData.includes(rule.value);
    }

    // Single checkbox
    return formData === false;
}

function hasFileSelected(rule, value) {
    return value[rule.property_id].length > 0;
}

function hasNoFileSelected(rule, value) {
    return value[rule.property_id].length === 0;
}
