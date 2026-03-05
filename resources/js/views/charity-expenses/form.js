import { createApp } from "vue";
import VueCurrencyInput from "../../components/Forms/Helpers/VueCurrencyInput.vue";
import YearPicker from "../../components/Forms/Helpers/YearPicker.vue";
import flatpickr from "flatpickr";

const currencyOptions = {
    currency: 'IDR',
    currencyDisplay: 'narrowSymbol',
    precision: 2,
    autoDecimalDigits: true,
    hideCurrencySymbolOnFocus: true,
    hideGroupingSeparatorOnFocus: false,
};

const initAmountInput = () => {
    const wrapper = document.getElementById('charity-expense-amount');
    if (!wrapper || wrapper.__vueApp) {
        return;
    }

    const inputId = wrapper.dataset.inputId;
    const hiddenInput = document.getElementById(inputId);

    const app = createApp({
        components: { VueCurrencyInput },
        data() {
            return {
                value: hiddenInput?.value || '',
                options: currencyOptions,
            };
        },
        watch: {
            value(newValue) {
                if (!hiddenInput) {
                    return;
                }
                hiddenInput.value = newValue ?? '';
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            },
        },
        mounted() {
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('message.processed', () => {
                    if (hiddenInput && hiddenInput.value !== this.value) {
                        this.value = hiddenInput.value;
                    }
                });
            }
        },
        template: `
            <vue-currency-input v-model="value" :options="options" class="form-control"></vue-currency-input>
        `,
    });

    wrapper.__vueApp = app;
    app.mount(wrapper);
};

const getLivewireId = () => {
    const root = document.getElementById('charity-expense-root');
    return root?.getAttribute('wire:id');
};

const initDatePicker = () => {
    const input = document.getElementById('charity-expense-date');
    const syncInput = document.getElementById('charity-expense-date-sync');
    if (!input || input._flatpickr) {
        return;
    }

    const livewireId = getLivewireId();

    flatpickr(input, {
        dateFormat: 'Y-m-d',
        defaultDate: syncInput?.value || input.value || null,
        onChange: (dates, dateStr) => {
            if (syncInput) {
                syncInput.value = dateStr;
                syncInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            if (window.Livewire && livewireId) {
                window.Livewire.find(livewireId)?.set('expense_date', dateStr);
            }
        },
    });
};

const initYearPicker = () => {
    const input = document.getElementById('finance-year-picker');
    const syncInput = document.getElementById('finance-year-sync');
    if (!input || input.__vueApp) {
        return;
    }

    const currentYear = syncInput?.value || input.getAttribute('data-year') || new Date().getFullYear();

    const app = createApp({
        components: { YearPicker },
        data() {
            return {
                year: String(currentYear),
            };
        },
        watch: {
            year(value) {
                if (syncInput) {
                    syncInput.value = value ?? '';
                    syncInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                const livewireId = getLivewireId();
                if (window.Livewire && livewireId) {
                    window.Livewire.find(livewireId)?.set('year', value);
                }
            },
        },
        template: `
            <year-picker
                v-model="year"
                label=""
                :start="5"
                :end="5"
                input-class="form-control form-control-sm"
                style="max-width: 140px;"
            ></year-picker>
        `,
    });

    input.__vueApp = app;
    app.mount(input);
};

const loadApexCharts = () => new Promise((resolve) => {
    if (window.ApexCharts) {
        resolve();
        return;
    }
    const script = document.createElement('script');
    script.src = '/assets/libs/apexcharts/apexcharts.min.js';
    script.onload = () => resolve();
    document.head.appendChild(script);
});

const initFinanceWidgets = () => {
    initAmountInput();
    initDatePicker();
    initYearPicker();
    initSelectSync();
    renderCharts();
    initChartObservers();
};

const initSelectSync = () => {
    const livewireId = getLivewireId();
    if (!livewireId || !window.Livewire) {
        return;
    }

    const bindSelect = (selector, prop) => {
        const element = document.querySelector(selector);
        if (!element || element.dataset.livewireBound) {
            return;
        }
        element.addEventListener('change', (event) => {
            window.Livewire.find(livewireId)?.set(prop, event.target.value);
        });
        element.dataset.livewireBound = '1';
    };

    bindSelect('select[wire\\:model\\.live="source_type"], select[wire\\:model="source_type"]', 'source_type');
    bindSelect('select[wire\\:model\\.live="expense_type"], select[wire\\:model="expense_type"]', 'expense_type');
};

const initChartObservers = () => {
    const classEl = document.getElementById('finance-chart-classes');
    const recipientEl = document.getElementById('finance-chart-recipients');

    const attachObserver = (el) => {
        if (!el || el._chartObserver) {
            return;
        }
        const observer = new MutationObserver((mutations) => {
            if (mutations.some((mutation) => mutation.type === 'attributes' && mutation.attributeName === 'data-chart')) {
                renderCharts();
            }
        });
        observer.observe(el, { attributes: true });
        el._chartObserver = observer;
    };

    attachObserver(classEl);
    attachObserver(recipientEl);
};

const renderCharts = (overridePayload = null) => {
    const classEl = document.getElementById('finance-chart-classes');
    const recipientEl = document.getElementById('finance-chart-recipients');
    const payload = overridePayload || window.financeChartsPayload || null;

    loadApexCharts().then(() => {
        if (!window.ApexCharts) {
            return;
        }

    if (classEl) {
        const chartData = payload?.classes || JSON.parse(classEl.dataset.chart || '{}');
        if (classEl._chart) {
            classEl._chart.destroy();
        }
        if (!chartData.labels || chartData.labels.length === 0) {
            classEl.innerHTML = `<div class="text-muted small">${window.messages?.data_not_found || 'No data'}</div>`;
        } else {
            classEl.innerHTML = '';
            const options = {
                chart: { type: 'bar', height: 280, toolbar: { show: false } },
                series: [
                    { name: window.messages?.total_money || 'Total Money', data: chartData.money || [] },
                    { name: window.messages?.total_rice || 'Total Rice', data: chartData.rice || [] },
                ],
                xaxis: { categories: chartData.labels || [] },
                plotOptions: {
                    bar: { columnWidth: '55%', dataLabels: { position: 'top' } },
                },
                dataLabels: { enabled: false },
                yaxis: [
                    {
                        seriesName: window.messages?.total_money || 'Total Money',
                        min: 0,
                        labels: {
                            formatter: (value) => new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(value),
                        },
                    },
                    {
                        seriesName: window.messages?.total_rice || 'Total Rice',
                        opposite: true,
                        min: 0,
                        labels: {
                            formatter: (value) => new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value),
                        },
                    },
                ],
            };
            classEl._chart = new window.ApexCharts(classEl, options);
            classEl._chart.render();
        }
    }

    if (recipientEl) {
        const chartData = payload?.recipients || JSON.parse(recipientEl.dataset.chart || '{}');
        if (recipientEl._chart) {
            recipientEl._chart.destroy();
        }
        if (!chartData.labels || chartData.labels.length === 0) {
            recipientEl.innerHTML = `<div class="text-muted small">${window.messages?.data_not_found || 'No data'}</div>`;
        } else {
            recipientEl.innerHTML = '';
            const options = {
                chart: { type: 'donut', height: 280 },
                labels: chartData.labels || [],
                series: chartData.series || [],
                legend: { position: 'bottom' },
            };
            recipientEl._chart = new window.ApexCharts(recipientEl, options);
            recipientEl._chart.render();
        }
    }
    });
};

document.addEventListener('DOMContentLoaded', initFinanceWidgets);

if (window.Livewire && typeof window.Livewire.hook === 'function') {
    window.Livewire.hook('message.processed', () => {
        initFinanceWidgets();
        const yearSync = document.getElementById('finance-year-sync');
        const yearInput = document.getElementById('finance-year-picker');
        if (yearInput?.__vueApp && yearSync && yearSync.value) {
            const instance = yearInput.__vueApp._instance;
            if (instance?.proxy) {
                instance.proxy.year = String(yearSync.value);
            }
        }

        const dateSync = document.getElementById('charity-expense-date-sync');
        const dateInput = document.getElementById('charity-expense-date');
        if (dateInput?._flatpickr && dateSync) {
            const value = dateSync.value || null;
            dateInput._flatpickr.setDate(value, false, "Y-m-d");
        }
    });

    if (typeof window.Livewire.on === 'function') {
        window.Livewire.on('finance:charts-refresh', () => {
            setTimeout(renderCharts, 0);
        });
        window.Livewire.on('finance:charts-data', (payload) => {
            const detail = Array.isArray(payload) ? (payload[0] || {}) : (payload || {});
            const charts = detail.charts || detail || null;
            window.financeChartsPayload = charts;
            setTimeout(() => renderCharts(charts), 0);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
        tab.addEventListener('shown.bs.tab', (event) => {
            const target = event.target?.getAttribute('data-bs-target');
            if (target === '#charity-tab-finance') {
                setTimeout(() => renderCharts(), 50);
            }
        });
    });
});
