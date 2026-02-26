import { createApp, watch } from "vue";
import { useCurrencyInput } from "vue-currency-input";

function parseInitialValue(input) {
    const raw = input.dataset.initial;
    if (raw === undefined || raw === null || raw === '') {
        return null;
    }

    const parsed = Number(raw);
    return Number.isNaN(parsed) ? null : parsed;
}

function mountCurrencyInput(input, livewireId, field, options) {
    if (input.dataset.mounted === '1') {
        return;
    }

    const app = createApp({
        setup() {
            const { inputRef, numberValue, setValue } = useCurrencyInput(options);

            watch(numberValue, (value) => {
                const numeric = value ?? null;
                window.Livewire.find(livewireId)?.set(field, numeric);
            });

            return {
                inputRef,
                setValue,
            };
        },
        mounted() {
            this.inputRef = input;
            const initial = parseInitialValue(input);
            if (initial !== null) {
                this.setValue(initial);
            }

            input._vci = { setValue: this.setValue };
            input.dataset.mounted = '1';
        },
        template: '<div></div>',
    });

    const container = document.createElement('div');
    input.parentElement?.appendChild(container);
    app.mount(container);
}

function mountCurrencyWidgets() {
    document.querySelectorAll('[data-livewire-currency]').forEach((wrapper) => {
        const livewireId = wrapper.dataset.livewireId;
        if (!livewireId || !window.Livewire) {
            return;
        }

        const currencyCode = wrapper.dataset.currencyCode || 'IDR';

        const options = {
            currency: currencyCode,
            currencyDisplay: 'narrowSymbol',
            precision: 2,
            autoDecimalDigits: true,
            hideCurrencySymbolOnFocus: true,
            hideGroupingSeparatorOnFocus: false,
        };

        wrapper.querySelectorAll('[data-currency-field]').forEach((input) => {
            const field = input.dataset.currencyField;
            if (!field) {
                return;
            }

            mountCurrencyInput(input, livewireId, field, options);
        });
    });
}

if (window.Livewire) {
    window.Livewire.hook('message.processed', () => {
        mountCurrencyWidgets();
    });
}

document.addEventListener('livewire:initialized', () => {
    mountCurrencyWidgets();
});

const handleCurrencySync = (payload = {}) => {
    const detail = Array.isArray(payload) ? (payload[0] || {}) : (payload || {});
    const livewireId = detail.id;
    if (!livewireId) {
        return;
    }

    const wrapper = document.querySelector(`[data-livewire-currency][data-livewire-id="${livewireId}"]`);
    if (!wrapper) {
        return;
    }

    const values = detail.values || {};

    wrapper.querySelectorAll('[data-currency-field]').forEach((input) => {
        const field = input.dataset.currencyField;
        if (!field || !input._vci) {
            return;
        }

        const value = Object.prototype.hasOwnProperty.call(values, field)
            ? values[field]
            : (Object.prototype.hasOwnProperty.call(detail, field) ? detail[field] : null);

        input._vci.setValue(value ?? null);
    });
};

window.addEventListener('currency-sync', (event) => {
    handleCurrencySync(event.detail || {});
});

if (window.Livewire && typeof window.Livewire.on === 'function') {
    window.Livewire.on('currency-sync', (payload) => handleCurrencySync(payload));
}

if (document.readyState === 'complete' || document.readyState === 'interactive') {
    mountCurrencyWidgets();
} else {
    document.addEventListener('DOMContentLoaded', mountCurrencyWidgets);
}
