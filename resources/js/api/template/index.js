import http from '@/libs/http';

export const getTemplateList = () => {
    return http.get('/cms/sms/template');
};

export const storeTemplate = (data) => {
    return http.post('/cms/sms/template', data);
};

export const updateTemplate = (id, data) => {
    return http.post(`/cms/sms/template/${id}`, data);
};

export const deleteTemplate = (id) => {
    return http.delete(`/cms/sms/template/${id}`);
};
