import {createApp} from "vue";

const payload = window.organizationForm || {
    mode: 'create',
    routes: {
        locations: {}
    },
    options: {
        countries: [],
        categories: [],
        types: [],
        statuses: [],
    },
    form: {
        profile: {},
    },
};

createApp({
    data() {
        const profile = payload.form.profile || {};

        return {
            mode: payload.mode,
            routes: payload.routes,
            options: payload.options,
            form: {
                ...payload.form,
                profile: {
                    description: profile.description ?? '',
                    address_line: profile.address_line ?? '',
                },
            },
            errors: {},
            loading: false,
            submit_form_key: 0,
            locationOptions: {
                provinces: [],
                cities: [],
                districts: [],
                villages: [],
                citizens: [],
                neighborhoods: [],
            },
        }
    },
    mounted() {
        this.initializeLocations();
        this.syncSlug();
    },
    watch: {
        'form.name': function () {
            this.syncSlug();
        },
    },
    methods: {
        syncSlug() {
            if (!this.form.name) {
                this.form.slug = '';
                return;
            }
            this.form.slug = this.form.name
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
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
        handleCountryChange() {
            this.form.province_id = null;
            this.form.city_id = null;
            this.form.district_id = null;
            this.form.village_id = null;
            this.form.citizens_association_id = null;
            this.form.neighborhood_association_id = null;
            this.locationOptions.provinces = [];
            this.locationOptions.cities = [];
            this.locationOptions.districts = [];
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.country_id) {
                this.fetchProvinces(this.form.country_id);
            }
        },
        handleProvinceChange() {
            this.form.city_id = null;
            this.form.district_id = null;
            this.form.village_id = null;
            this.form.citizens_association_id = null;
            this.form.neighborhood_association_id = null;
            this.locationOptions.cities = [];
            this.locationOptions.districts = [];
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.province_id) {
                this.fetchCities(this.form.province_id);
            }
        },
        handleCityChange() {
            this.form.district_id = null;
            this.form.village_id = null;
            this.form.citizens_association_id = null;
            this.form.neighborhood_association_id = null;
            this.locationOptions.districts = [];
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.city_id) {
                this.fetchDistricts(this.form.city_id);
            }
        },
        handleDistrictChange() {
            this.form.village_id = null;
            this.form.citizens_association_id = null;
            this.form.neighborhood_association_id = null;
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.district_id) {
                this.fetchVillages(this.form.district_id);
            }
        },
        handleVillageChange() {
            this.form.citizens_association_id = null;
            this.form.neighborhood_association_id = null;
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.village_id) {
                this.fetchCitizens(this.form.village_id);
            }
        },
        handleCitizensChange() {
            this.form.neighborhood_association_id = null;
            this.locationOptions.neighborhoods = [];

            if (this.form.citizens_association_id) {
                this.fetchNeighborhoods(this.form.citizens_association_id);
            }
        },
        async fetchProvinces(countryId) {
            try {
                const {data} = await axios.get(this.routes.locations.provinces, {params: {country_id: countryId}});
                this.locationOptions.provinces = data;
            } catch (e) {
                this.locationOptions.provinces = [];
            }
        },
        async fetchCities(provinceId) {
            try {
                const {data} = await axios.get(this.routes.locations.cities, {params: {province_id: provinceId}});
                this.locationOptions.cities = data;
            } catch (e) {
                this.locationOptions.cities = [];
            }
        },
        async fetchDistricts(cityId) {
            try {
                const {data} = await axios.get(this.routes.locations.districts, {params: {city_id: cityId}});
                this.locationOptions.districts = data;
            } catch (e) {
                this.locationOptions.districts = [];
            }
        },
        async fetchVillages(districtId) {
            try {
                const {data} = await axios.get(this.routes.locations.villages, {params: {district_id: districtId}});
                this.locationOptions.villages = data;
            } catch (e) {
                this.locationOptions.villages = [];
            }
        },
        async fetchCitizens(villageId) {
            try {
                const {data} = await axios.get(this.routes.locations.citizens, {params: {village_id: villageId}});
                this.locationOptions.citizens = data;
            } catch (e) {
                this.locationOptions.citizens = [];
            }
        },
        async fetchNeighborhoods(citizensAssociationId) {
            try {
                const {data} = await axios.get(this.routes.locations.neighborhoods, {
                    params: {citizens_association_id: citizensAssociationId}
                });
                this.locationOptions.neighborhoods = data;
            } catch (e) {
                this.locationOptions.neighborhoods = [];
            }
        },
        async submitForm(loading = true) {
            this.loading = loading;
            this.errors = {};

            const url = this.mode === 'edit' ? this.routes.update : this.routes.store;
            const data = JSON.parse(JSON.stringify(this.form));

            if (this.mode === 'edit') {
                data._method = 'PUT';
            }

            try {
                const response = await axios.post(url, data);
                if (response.data.redirect) {
                    window.location.href = response.data.redirect;
                }
            } catch (error) {
                if (error.response && error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                this.submit_form_key++;
            } finally {
                this.loading = false;
            }
        },
    }
}).mount('#organization-form-app');
