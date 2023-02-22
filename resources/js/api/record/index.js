import http from '@/libs/http';

export const getRecordList = (params) => {
    return http.get('/cms/sms/record', {params, timeout: 120000});
};

export const storeRecord = (data) => {
    return http.post('/cms/sms/record', data);
};

export const updateRecord = (id, data) => {
    return http.post(`/cms/sms/record/${id}`, data);
};

export const deleteRecord = (id) => {
    return http.delete(`/cms/sms/record/${id}`);
};
