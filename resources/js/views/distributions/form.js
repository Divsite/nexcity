import { createApp } from "vue";

const payload = window.distributionFormPayload || {
    form: {},
    routes: {},
    options: {},
    context: null,
    ui: { modal: true },
};

createApp({
    data() {
        return {
            form: {
                id: payload.form?.id || null,
                distribution_class_id: payload.form?.distribution_class_id || '',
                year: payload.form?.year || new Date().getFullYear(),
                country_id: payload.form?.country_id || '',
                province_id: payload.form?.province_id || '',
                city_id: payload.form?.city_id || '',
                district_id: payload.form?.district_id || '',
                village_id: payload.form?.village_id || '',
                citizens_association_id: payload.form?.citizens_association_id || '',
                neighborhood_association_id: payload.form?.neighborhood_association_id || '',
                use_manual_recipients: Boolean(payload.form?.use_manual_recipients),
                recipient_ids: Array.isArray(payload.form?.recipient_ids) ? payload.form.recipient_ids : [],
                manual_recipients: Array.isArray(payload.form?.manual_recipients) ? payload.form.manual_recipients : [],
                officer_ids: Array.isArray(payload.form?.officer_ids) ? payload.form.officer_ids : [],
            },
            options: payload.options || { distribution_classes: [], officers: [], countries: [] },
            routes: payload.routes || {},
            errors: {},
            loading: false,
            loadingResidents: false,
            residents: [],
            residentSearch: '',
            showAdvancedLocation: false,
            locationOptions: {
                provinces: [],
                cities: [],
                districts: [],
                villages: [],
                citizens: [],
                neighborhoods: [],
            },
            labels: payload.labels || {
                advanced_location: 'Advanced location',
                hide: 'Hide',
                search: 'Search',
                liter: 'liter',
            },
        };
    },
    watch: {
        'form.use_manual_recipients'(value) {
            if (value) {
                this.form.recipient_ids = [];
                if (this.form.manual_recipients.length === 0) {
                    this.addManualRecipient();
                }
            } else {
                this.form.manual_recipients = [];
            }
        },
        selectedClass(value) {
            if (!value) {
                return;
            }

            if (value.is_internal) {
                this.form.use_manual_recipients = false;
                this.form.recipient_ids = [];
                this.form.manual_recipients = [];
            }
        },
        'form.distribution_class_id'() {
            this.loadResidents();
        },
        'form.year'() {
            this.loadResidents();
        },
    },
    computed: {
        selectedClass() {
            const selectedId = Number(this.form.distribution_class_id);
            if (!selectedId) {
                return null;
            }
            return this.options.distribution_classes.find((item) => Number(item.id) === selectedId) || null;
        },
        isInternalClass() {
            return Boolean(this.selectedClass && this.selectedClass.is_internal);
        },
        filteredResidents() {
            const keyword = (this.residentSearch || '').toLowerCase();
            if (!keyword) {
                return this.residents;
            }
            return this.residents.filter((resident) => (resident.name || '').toLowerCase().includes(keyword));
        },
        allVisibleSelected: {
            get() {
                const visibleIds = this.filteredResidents
                    .filter((resident) => !resident.disabled)
                    .map((resident) => Number(resident.id));
                if (visibleIds.length === 0) {
                    return false;
                }
                const selected = this.form.recipient_ids.map((id) => Number(id));
                return visibleIds.every((id) => selected.includes(id));
            },
            set(value) {
                const visibleIds = this.filteredResidents
                    .filter((resident) => !resident.disabled)
                    .map((resident) => Number(resident.id));
                const selected = this.form.recipient_ids.map((id) => Number(id));
                if (value) {
                    const merged = [...selected, ...visibleIds];
                    this.form.recipient_ids = Array.from(new Set(merged));
                } else {
                    this.form.recipient_ids = selected.filter((id) => !visibleIds.includes(id));
                }
            },
        },
    },
    mounted() {
        this.initializeLocations().then(() => {
            this.loadResidents();
        });

        const params = new URLSearchParams(window.location.search);
        const editId = params.get('edit');
        if (editId) {
            this.loadEditForm(editId);
        }

        const modalEl = document.getElementById('distribution-modal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', () => {
                this.clearEditParam();
            });
            modalEl.addEventListener('hidden.bs.modal', () => {
                this.resetForm();
                this.clearEditParam();
            });
        }
    },
    methods: {
        formatMoney(value) {
            const amount = Number(value || 0);
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
            }).format(amount);
        },
        formatRice(value) {
            const amount = Number(value || 0);
            const formatted = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }).format(Number.isNaN(amount) ? 0 : amount);
            return `${formatted} ${this.labels.liter || 'liter'}`;
        },
        async initializeLocations() {
            if (this.form.country_id) {
                await this.fetchProvinces(this.form.country_id);
            }
            if (this.form.province_id) {
                await this.fetchCities(this.form.province_id);
            }
            if (this.form.city_id) {
                await this.fetchDistricts(this.form.city_id);
            }
            if (this.form.district_id) {
                await this.fetchVillages(this.form.district_id);
            }
            if (this.form.village_id) {
                await this.fetchCitizens(this.form.village_id);
            }
            if (this.form.citizens_association_id) {
                await this.fetchNeighborhoods(this.form.citizens_association_id);
            }
        },
        resetLocation() {
            this.form.province_id = '';
            this.form.city_id = '';
            this.form.district_id = '';
            this.form.village_id = '';
            this.form.citizens_association_id = '';
            this.form.neighborhood_association_id = '';
            this.locationOptions = {
                provinces: [],
                cities: [],
                districts: [],
                villages: [],
                citizens: [],
                neighborhoods: [],
            };
        },
        handleCountryChange() {
            this.resetLocation();
            if (this.form.country_id) {
                this.fetchProvinces(this.form.country_id);
            }
            this.loadResidents();
        },
        handleProvinceChange() {
            this.form.city_id = '';
            this.form.district_id = '';
            this.form.village_id = '';
            this.form.citizens_association_id = '';
            this.form.neighborhood_association_id = '';
            this.locationOptions.cities = [];
            this.locationOptions.districts = [];
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];
            if (this.form.province_id) {
                this.fetchCities(this.form.province_id);
            }
            this.loadResidents();
        },
        handleCityChange() {
            this.form.district_id = '';
            this.form.village_id = '';
            this.form.citizens_association_id = '';
            this.form.neighborhood_association_id = '';
            this.locationOptions.districts = [];
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];
            if (this.form.city_id) {
                this.fetchDistricts(this.form.city_id);
            }
            this.loadResidents();
        },
        handleDistrictChange() {
            this.form.village_id = '';
            this.form.citizens_association_id = '';
            this.form.neighborhood_association_id = '';
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];
            if (this.form.district_id) {
                this.fetchVillages(this.form.district_id);
            }
            this.loadResidents();
        },
        handleVillageChange() {
            this.form.citizens_association_id = '';
            this.form.neighborhood_association_id = '';
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];
            if (this.form.village_id) {
                this.fetchCitizens(this.form.village_id);
            }
            this.loadResidents();
        },
        handleCitizensChange() {
            this.form.neighborhood_association_id = '';
            this.locationOptions.neighborhoods = [];
            if (this.form.citizens_association_id) {
                this.fetchNeighborhoods(this.form.citizens_association_id);
            }
            this.loadResidents();
        },
        async fetchProvinces(countryId) {
            const { data } = await axios.get(this.routes.locations.provinces, { params: { country_id: countryId } });
            this.locationOptions.provinces = data || [];
        },
        async fetchCities(provinceId) {
            const { data } = await axios.get(this.routes.locations.cities, { params: { province_id: provinceId } });
            this.locationOptions.cities = data || [];
        },
        async fetchDistricts(cityId) {
            const { data } = await axios.get(this.routes.locations.districts, { params: { city_id: cityId } });
            this.locationOptions.districts = data || [];
        },
        async fetchVillages(districtId) {
            const { data } = await axios.get(this.routes.locations.villages, { params: { district_id: districtId } });
            this.locationOptions.villages = data || [];
        },
        async fetchCitizens(villageId) {
            const { data } = await axios.get(this.routes.locations.citizens, { params: { village_id: villageId } });
            this.locationOptions.citizens = data || [];
        },
        async fetchNeighborhoods(citizensAssociationId) {
            const { data } = await axios.get(this.routes.locations.neighborhoods, { params: { citizens_association_id: citizensAssociationId } });
            this.locationOptions.neighborhoods = data || [];
        },
        async loadResidents() {
            this.loadingResidents = true;
            if (!this.form.id) {
                this.form.recipient_ids = [];
            }
            try {
                const params = {};
                if (this.form.id) {
                    params.distribution_id = this.form.id;
                }
                if (this.form.distribution_class_id) {
                    params.distribution_class_id = this.form.distribution_class_id;
                }
                if (this.form.year) {
                    params.year = this.form.year;
                }
                if (this.residentSearch) {
                    params.search = this.residentSearch;
                }
                if (this.form.country_id) params.country_id = this.form.country_id;
                if (this.form.province_id) params.province_id = this.form.province_id;
                if (this.form.city_id) params.city_id = this.form.city_id;
                if (this.form.district_id) params.district_id = this.form.district_id;
                if (this.form.village_id) params.village_id = this.form.village_id;
                if (this.form.citizens_association_id) params.citizens_association_id = this.form.citizens_association_id;
                if (this.form.neighborhood_association_id) params.neighborhood_association_id = this.form.neighborhood_association_id;

                const { data } = await axios.get(this.routes.residents, { params });
                this.residents = Array.isArray(data) ? data : [];
            } catch (error) {
                this.residents = [];
            } finally {
                this.loadingResidents = false;
            }
        },
        addManualRecipient() {
            this.form.manual_recipients.push({ name: '', phone: '', address: '' });
        },
        removeManualRecipient(index) {
            this.form.manual_recipients.splice(index, 1);
        },
        buildPayload() {
            return {
                _mode: 'modal',
                distribution_class_id: this.form.distribution_class_id,
                year: this.form.year,
                country_id: this.form.country_id || null,
                province_id: this.form.province_id || null,
                city_id: this.form.city_id || null,
                district_id: this.form.district_id || null,
                village_id: this.form.village_id || null,
                citizens_association_id: this.form.citizens_association_id || null,
                neighborhood_association_id: this.form.neighborhood_association_id || null,
                officer_ids: this.form.officer_ids || [],
                use_manual_recipients: this.form.use_manual_recipients ? 1 : 0,
                recipient_ids: this.form.use_manual_recipients ? [] : this.form.recipient_ids,
                manual_recipients: this.form.use_manual_recipients ? this.form.manual_recipients : [],
            };
        },
        resetForm() {
            this.form.id = null;
            this.form.distribution_class_id = '';
            this.form.recipient_ids = [];
            this.form.manual_recipients = [];
            this.form.use_manual_recipients = false;
            this.form.officer_ids = [];
            this.errors = {};
        },
        closeModal() {
            const element = document.getElementById('distribution-modal');
            if (!element || !window.bootstrap) {
                return;
            }
            window.bootstrap.Modal.getOrCreateInstance(element).hide();
            this.clearEditParam();
        },
        clearEditParam() {
            try {
                const url = new URL(window.location.href);
                if (url.searchParams.has('edit')) {
                    url.searchParams.delete('edit');
                    window.history.replaceState({}, '', url.toString());
                }
            } catch (error) {
                // ignore
            }
        },
        notifySuccess(message) {
            if (window.Swal) {
                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: message || 'Saved successfully',
                    timer: 2200,
                    timerProgressBar: true,
                    showConfirmButton: false,
                });
            }
        },
        async loadEditForm(id) {
            if (!id) {
                return;
            }

            try {
                const response = await axios.get(`/mosque/charity-distributions/${id}/form`);
                const payload = response.data || {};
                this.applyPayload(payload);
                this.openModal();
            } catch (error) {
                // ignore
            }
        },
        applyPayload(payloadData) {
            this.form = {
                id: payloadData.form?.id || null,
                distribution_class_id: payloadData.form?.distribution_class_id || '',
                year: payloadData.form?.year || new Date().getFullYear(),
                country_id: payloadData.form?.country_id || '',
                province_id: payloadData.form?.province_id || '',
                city_id: payloadData.form?.city_id || '',
                district_id: payloadData.form?.district_id || '',
                village_id: payloadData.form?.village_id || '',
                citizens_association_id: payloadData.form?.citizens_association_id || '',
                neighborhood_association_id: payloadData.form?.neighborhood_association_id || '',
                use_manual_recipients: Boolean(payloadData.form?.use_manual_recipients),
                recipient_ids: Array.isArray(payloadData.form?.recipient_ids) ? payloadData.form.recipient_ids : [],
                manual_recipients: Array.isArray(payloadData.form?.manual_recipients) ? payloadData.form.manual_recipients : [],
                officer_ids: Array.isArray(payloadData.form?.officer_ids) ? payloadData.form.officer_ids : [],
            };
            this.options = payloadData.options || this.options;
            this.routes = payloadData.routes || this.routes;
            this.errors = {};
            this.showAdvancedLocation = false;
            this.initializeLocations().then(() => {
                this.loadResidents();
            });
        },
        openModal() {
            const element = document.getElementById('distribution-modal');
            if (!element || !window.bootstrap) {
                return;
            }
            window.bootstrap.Modal.getOrCreateInstance(element).show();
        },
        async submitForm() {
            this.loading = true;
            this.errors = {};
            try {
                let response;
                if (this.form.id && this.routes.update) {
                    response = await axios.put(this.routes.update, this.buildPayload());
                } else {
                    response = await axios.post(this.routes.store, this.buildPayload());
                }
                if (response.data && response.data.success) {
                    this.notifySuccess(response.data.message || 'Saved');
                    this.closeModal();
                    this.resetForm();
                    if (window.Livewire) {
                        if (typeof window.Livewire.dispatch === 'function') {
                            window.Livewire.dispatch('distributionSaved');
                            window.Livewire.dispatch('refreshDistributionTable');
                        }
                        if (typeof window.Livewire.emit === 'function') {
                            window.Livewire.emit('distributionSaved');
                            window.Livewire.emit('refreshDistributionTable');
                        }
                        if (typeof window.Livewire.emitTo === 'function') {
                            window.Livewire.emitTo('distributions.distribution-table', '$refresh');
                        }
                    }
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors || {};
                }
            } finally {
                this.loading = false;
            }
        },
    },
}).mount('#distribution-form');
