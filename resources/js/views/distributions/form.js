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
    },
    computed: {
        selectedClass() {
            const selectedId = Number(this.form.distribution_class_id);
            if (!selectedId) {
                return null;
            }
            return this.options.distribution_classes.find((item) => Number(item.id) === selectedId) || null;
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
                const visibleIds = this.filteredResidents.map((resident) => Number(resident.id));
                if (visibleIds.length === 0) {
                    return false;
                }
                const selected = this.form.recipient_ids.map((id) => Number(id));
                return visibleIds.every((id) => selected.includes(id));
            },
            set(value) {
                const visibleIds = this.filteredResidents.map((resident) => Number(resident.id));
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
            const amount = value ? Number(value) : 0;
            return `${amount} ${this.labels.liter || 'liter'}`;
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
            this.form.recipient_ids = [];
            try {
                const params = {
                    search: this.residentSearch || undefined,
                    country_id: this.form.country_id || undefined,
                    province_id: this.form.province_id || undefined,
                    city_id: this.form.city_id || undefined,
                    district_id: this.form.district_id || undefined,
                    village_id: this.form.village_id || undefined,
                    citizens_association_id: this.form.citizens_association_id || undefined,
                    neighborhood_association_id: this.form.neighborhood_association_id || undefined,
                };
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
        async submitForm() {
            this.loading = true;
            this.errors = {};
            try {
                const response = await axios.post(this.routes.store, this.buildPayload());
                if (response.data && response.data.success) {
                    this.notifySuccess(response.data.message || 'Saved');
                    this.closeModal();
                    this.resetForm();
                    if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                        window.Livewire.dispatch('distributionSaved');
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
