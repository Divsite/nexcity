<template>
    <div class="year-picker" ref="pickerRef">
        <input
            type="text"
            :class="inputClass"
            :value="displayValue"
            readonly
            @click="togglePicker"
        />
        <div class="year-picker-dropdown" v-if="open">
            <div class="year-picker-header">{{ label }}</div>
            <div class="year-picker-grid">
                <button
                    v-for="year in yearOptions"
                    :key="year"
                    type="button"
                    class="year-picker-item"
                    :class="{ 'is-active': String(year) === String(displayValue) }"
                    @click="selectYear(year)"
                >
                    {{ year }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'YearPicker',
    props: {
        modelValue: {
            type: [String, Number],
            default: null,
        },
        start: {
            type: Number,
            default: 5,
        },
        end: {
            type: Number,
            default: 5,
        },
        label: {
            type: String,
            default: 'Year',
        },
        inputClass: {
            type: String,
            default: 'form-control',
        },
    },
    emits: ['update:modelValue'],
    data() {
        return {
            open: false,
        };
    },
    computed: {
        displayValue() {
            return this.modelValue ? String(this.modelValue) : '';
        },
        yearOptions() {
            const baseYear = Number(this.displayValue) || new Date().getFullYear();
            const start = baseYear - this.start;
            const end = baseYear + this.end;
            const years = [];

            for (let year = start; year <= end; year += 1) {
                years.push(year);
            }

            return years;
        },
    },
    mounted() {
        document.addEventListener('click', this.handleOutsideClick);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleOutsideClick);
    },
    methods: {
        togglePicker() {
            this.open = !this.open;
        },
        selectYear(year) {
            this.$emit('update:modelValue', String(year));
            this.open = false;
        },
        handleOutsideClick(event) {
            const picker = this.$refs.pickerRef;
            if (!picker) {
                return;
            }
            if (picker.contains(event.target)) {
                return;
            }
            this.open = false;
        },
    },
};
</script>

<style scoped>
.year-picker {
    position: relative;
}

.year-picker-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 240px;
    margin-top: 6px;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
    z-index: 1050;
}

.year-picker-header {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6c757d;
    margin-bottom: 8px;
}

.year-picker-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
}

.year-picker-item {
    border: 1px solid transparent;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 6px 0;
    font-weight: 600;
    color: #111827;
    transition: all 0.15s ease;
}

.year-picker-item:hover {
    background: #eef2ff;
    border-color: #c7d2fe;
}

.year-picker-item.is-active {
    background: #4f46e5;
    color: #fff;
    border-color: #4338ca;
}
</style>
