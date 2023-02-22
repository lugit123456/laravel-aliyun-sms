import http from '@/libs/http';

export const getSignList = () => {
    return http.get('/cms/sms/sign');
};

export const storeSign = (data) => {
    return http.post('/cms/sms/sign', data);
};

export const updateSign = (id, data) => {
    return http.post(`/cms/sms/sign/${id}`, data);
};

export const deleteSign = (id) => {
    return http.delete(`/cms/sms/sign/${id}`);
};
