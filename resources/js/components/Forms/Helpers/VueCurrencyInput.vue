<template>
    <input ref="inputRef" type="text" v-bind="$attrs" />
</template>

<script>
import { watch } from 'vue';
import { useCurrencyInput } from 'vue-currency-input';

const toNumber = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const numeric = Number(value);

    return Number.isNaN(numeric) ? null : numeric;
};

export default {
    name: 'VueCurrencyInput',
    inheritAttrs: false,
    props: {
        modelValue: {
            type: [Number, String],
            default: null,
        },
        options: {
            type: Object,
            default: () => ({}),
        },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const { inputRef, numberValue, setValue } = useCurrencyInput(props.options, false);

        watch(
            () => props.modelValue,
            (value) => {
                setValue(toNumber(value));
            },
            { immediate: true }
        );

        watch(numberValue, (value) => {
            emit('update:modelValue', toNumber(value));
        });

        return { inputRef };
    },
}
</script>
