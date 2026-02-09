import {createApp} from "vue";
import {select2} from "../../directives/select2";

const payload = window.residentForm || {
    mode: 'create',
    context: null,
    routes: {
        locations: {}
    },
    options: {
        countries: [],
        organizations: [],
        genders: [],
        residence_statuses: [],
        marital_statuses: [],
        educations: [],
        education_majors: [],
        religions: [],
    },
    form: {
        profile: {},
    },
};

createApp({
    data() {
        const profile = payload.form.profile || {};
        const interests = Array.isArray(profile.interests) ? profile.interests : [];
        const talents = Array.isArray(profile.talents) ? profile.talents : [];
        const housePhotos = Array.isArray(profile.house_photo_paths) ? profile.house_photo_paths : [];

        return {
            mode: payload.mode,
            context: payload.context,
            routes: payload.routes,
            options: payload.options,
            form: {
                ...payload.form,
                password: payload.form.password ?? '',
                profile: {
                    organization_id: profile.organization_id ?? '',
                    country_id: profile.country_id ?? '',
                    province_id: profile.province_id ?? '',
                    city_id: profile.city_id ?? '',
                    district_id: profile.district_id ?? '',
                    village_id: profile.village_id ?? '',
                    citizens_association_id: profile.citizens_association_id ?? '',
                    neighborhood_association_id: profile.neighborhood_association_id ?? '',
                    national_id_number: profile.national_id_number ?? '',
                    family_card_number: profile.family_card_number ?? '',
                    birth_place: profile.birth_place ?? '',
                    birth_date: profile.birth_date ?? '',
                    gender: profile.gender ?? '',
                    residence_status_id: profile.residence_status_id ?? '',
                    marital_status_id: profile.marital_status_id ?? '',
                    education_id: profile.education_id ?? '',
                    education_major_id: profile.education_major_id ?? '',
                    religion_id: profile.religion_id ?? '',
                    occupation: profile.occupation ?? '',
                    is_head_family: Boolean(profile.is_head_family ?? false),
                    family_members_count: profile.family_members_count ?? 0,
                    address_line: profile.address_line ?? '',
                    ktp_photo_path: profile.ktp_photo_path ?? '',
                },
            },
            interestsText: interests.join(', '),
            talentsText: talents.join(', '),
            housePhotosText: housePhotos.join(', '),
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
            previewToken: Math.random().toString(36).slice(2, 6),
        }
    },
    directives: {select2},
    mounted() {
        if (this.isPartner) {
            this.applyPartnerDefaults();
        }
        this.initializeLocations();
        this.initializeSelect2();
    },
    computed: {
        isPartner() {
            return this.context && this.context.mode === 'partner';
        },
        usernamePreview() {
            if (!this.isPartner) {
                return this.form.username;
            }

            const nameSlug = this.slugify(this.form.name || 'resident');
            const meta = this.context?.location_meta || {};
            const parts = [
                meta.rt ? `rt${String(meta.rt).padStart(3, '0')}` : null,
                meta.rw ? `rw${String(meta.rw).padStart(3, '0')}` : null,
                this.shortSlug(meta.village),
                this.shortSlug(meta.district),
                this.shortSlug(meta.city),
                this.shortSlug(meta.province),
            ].filter(Boolean);

            const suffix = parts.length ? parts.join('.') : 'resident';

            return `${nameSlug}.${suffix}.${this.previewToken}`;
        },
        filteredEducationMajors() {
            const educationId = Number(this.form.profile.education_id);
            if (!educationId) {
                return this.options.education_majors || [];
            }

            return (this.options.education_majors || []).filter(
                (major) => Number(major.education_id) === educationId
            );
        }
    },
    methods: {
        slugify(value) {
            return value
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '.')
                .replace(/(^\.+|\.+$)/g, '')
                .replace(/\.+/g, '.');
        },
        shortSlug(value) {
            if (!value) {
                return null;
            }
            const slug = this.slugify(value).replace(/\./g, '');
            return slug.length > 6 ? slug.substring(0, 6) : slug;
        },
        applyPartnerDefaults() {
            const location = (this.context && this.context.location) ? this.context.location : {};
            this.form.profile.organization_id = this.context?.organization_id ?? this.form.profile.organization_id;
            this.form.profile.country_id = location.country_id ?? this.form.profile.country_id;
            this.form.profile.province_id = location.province_id ?? this.form.profile.province_id;
            this.form.profile.city_id = location.city_id ?? this.form.profile.city_id;
            this.form.profile.district_id = location.district_id ?? this.form.profile.district_id;
            this.form.profile.village_id = location.village_id ?? this.form.profile.village_id;
            this.form.profile.citizens_association_id = location.citizens_association_id ?? this.form.profile.citizens_association_id;
            this.form.profile.neighborhood_association_id = location.neighborhood_association_id ?? this.form.profile.neighborhood_association_id;
        },
        initializeSelect2() {
            this.$nextTick(() => {
                if (window.$) {
                    $(".select2").select2();
                }
            });
        },
        async initializeLocations() {
            if (this.form.profile.country_id) {
                await this.fetchProvinces(this.form.profile.country_id);
            }
            if (this.form.profile.province_id) {
                await this.fetchCities(this.form.profile.province_id);
            }
            if (this.form.profile.city_id) {
                await this.fetchDistricts(this.form.profile.city_id);
            }
            if (this.form.profile.district_id) {
                await this.fetchVillages(this.form.profile.district_id);
            }
            if (this.form.profile.village_id) {
                await this.fetchCitizens(this.form.profile.village_id);
            }
            if (this.form.profile.citizens_association_id) {
                await this.fetchNeighborhoods(this.form.profile.citizens_association_id);
            }
        },
        handleCountryChange() {
            this.form.profile.province_id = null;
            this.form.profile.city_id = null;
            this.form.profile.district_id = null;
            this.form.profile.village_id = null;
            this.form.profile.citizens_association_id = null;
            this.form.profile.neighborhood_association_id = null;
            this.locationOptions.provinces = [];
            this.locationOptions.cities = [];
            this.locationOptions.districts = [];
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.profile.country_id) {
                this.fetchProvinces(this.form.profile.country_id);
            }
        },
        handleProvinceChange() {
            this.form.profile.city_id = null;
            this.form.profile.district_id = null;
            this.form.profile.village_id = null;
            this.form.profile.citizens_association_id = null;
            this.form.profile.neighborhood_association_id = null;
            this.locationOptions.cities = [];
            this.locationOptions.districts = [];
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.profile.province_id) {
                this.fetchCities(this.form.profile.province_id);
            }
        },
        handleCityChange() {
            this.form.profile.district_id = null;
            this.form.profile.village_id = null;
            this.form.profile.citizens_association_id = null;
            this.form.profile.neighborhood_association_id = null;
            this.locationOptions.districts = [];
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.profile.city_id) {
                this.fetchDistricts(this.form.profile.city_id);
            }
        },
        handleDistrictChange() {
            this.form.profile.village_id = null;
            this.form.profile.citizens_association_id = null;
            this.form.profile.neighborhood_association_id = null;
            this.locationOptions.villages = [];
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.profile.district_id) {
                this.fetchVillages(this.form.profile.district_id);
            }
        },
        handleVillageChange() {
            this.form.profile.citizens_association_id = null;
            this.form.profile.neighborhood_association_id = null;
            this.locationOptions.citizens = [];
            this.locationOptions.neighborhoods = [];

            if (this.form.profile.village_id) {
                this.fetchCitizens(this.form.profile.village_id);
            }
        },
        handleCitizensChange() {
            this.form.profile.neighborhood_association_id = null;
            this.locationOptions.neighborhoods = [];

            if (this.form.profile.citizens_association_id) {
                this.fetchNeighborhoods(this.form.profile.citizens_association_id);
            }
        },
        handleEducationChange() {
            this.form.profile.education_major_id = null;
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

            data.profile.interests = this.interestsText
                ? this.interestsText.split(',').map((item) => item.trim()).filter(Boolean)
                : [];
            data.profile.talents = this.talentsText
                ? this.talentsText.split(',').map((item) => item.trim()).filter(Boolean)
                : [];
            data.profile.house_photo_paths = this.housePhotosText
                ? this.housePhotosText.split(',').map((item) => item.trim()).filter(Boolean)
                : [];

            if (this.isPartner) {
                data.username = this.usernamePreview;
            }

            if (!data.password) {
                delete data.password;
            }

            if (this.mode === 'edit') {
                delete data.password;
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
}).mount('#resident-form-app');
