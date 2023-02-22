<template>
    <div class="inner-container">
        <sticky class-name="sub-navbar" style="margin-bottom: 1rem;" :zIndex="5">
            <el-button type="success" @click="openCreateDialog">{{ $t('table.add') }}</el-button>
            <el-button @click="onBack">{{ $t('table.back') }}</el-button>
        </sticky>

        <el-table
            :data="list"
            border
            row-key="id"
            ref="listTable"
            v-show="list"
            v-loading="listLoading"
            :row-class-name="setRecordClassName"
            style="width: 100%">
            <el-table-column
                width="250"
                :label="$t('record.title')">
                <template slot-scope="{row}">
                    <del v-if="row.deleted_at">{{ row.title }}</del>
                    <span v-else>{{ row.title }}</span>
                </template>
            </el-table-column>
            <el-table-column
                width="100"
                prop="sign.sign_name"
                :label="$t('sign.name')" />
            <el-table-column
                prop="template.content"
                :label="$t('template.content')" />
            <el-table-column
                :label="$t('record.sendDatetime')"
                width="180">
                <template slot-scope="{row}">
                    <del v-if="row.deleted_at">{{ row.send_datetime }}</del>
                    <span v-else>{{ row.send_datetime }}</span>
                </template>
            </el-table-column>
            <el-table-column
                width="120"
                :label="$t('common.status')">
                <template slot-scope="{row}">
                    <div v-if="row.results">
                        <div v-if="row.results.delivered">
                            {{ $t('record.countStatusText.success', {count: Object.keys(row.results.delivered).length})
                            }}
                        </div>
                        <div v-if="row.results.pending && row.results.pending.length">
                            {{ $t('record.countStatusText.pending', {count: row.results.pending.length}) }}
                        </div>
                        <div v-if="row.results.missing && row.results.missing.length">
                            {{ $t('record.countStatusText.missing', {count: row.results.missing.length}) }}
                        </div>
                        <div v-if="row.results.failed">
                            {{ $t('record.countStatusText.failed', {count: Object.keys(row.results.failed).length}) }}
                        </div>
                    </div>
                    <div v-else>
                        {{ $t('record.countStatusText.pending', {count: row.phone_numbers.split(',').length}) }}
                    </div>
                </template>
            </el-table-column>
            <el-table-column
                fixed="right"
                :label="$t('table.actions')"
                width="140">
                <template slot-scope="{row}">
                    <el-button type="text" size="small" @click="openEditDialog(row)">
                        {{ isFixed(row) ? $t('table.view') : $t('table.edit') }}
                    </el-button>
                    <el-button type="text" size="small" @click="onDelete(row.id)">
                        {{ $t('table.delete')}}
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <pagination v-show="total>this.listQuery.limit" :total="total" :page.sync="listQuery.page"
                    :limit.sync="listQuery.limit"
                    :autoScroll="true" layout="total, prev, pager, next, jumper" @pagination="getList" />

        <el-dialog
            :title="this.currentRecord ? this.currentRecord.title : dialogFormTitle[dialogFormMode]"
            :visible.sync="dialogFormVisible"
            :close-on-click-modal="false"
            @close="onDialogClose">
            <p>
                <el-tag type="danger" v-if="currentRecord.message && currentRecord.message !== 'OK'">
                    {{ currentRecord.message }}
                </el-tag>
            </p>
            <p v-if="isFixed(currentRecord) && currentRecord.results && currentRecord.results.delivered">
                <el-popover
                    placement="right-start"
                    width="320"
                    trigger="click">
                    <ul>
                        <li v-for="(item, phoneNumber) in currentRecord.results.delivered">
                            {{ `${item.ReceiveDate} => ${phoneNumber}` }}
                        </li>
                    </ul>
                    <el-tag type="success" slot="reference">
                        {{ $t('record.deliveredCount', {count: Object.keys(currentRecord.results.delivered).length})}}
                    </el-tag>
                </el-popover>
            </p>
            <p v-if="isFixed(currentRecord) && currentRecord.results && currentRecord.results.failed">
                <el-tag type="danger">
                    {{ $t('record.failedCount', {count: Object.keys(currentRecord.results.failed).length})}}
                </el-tag>
                <small v-for="(item, phoneNumber) in currentRecord.results.failed" style="display: block;">
                    {{ `${phoneNumber}: ${item.ErrCode}`}}
                </small>
            </p>
            <p v-if="isFixed(currentRecord) && currentRecord.results && currentRecord.results.missing && currentRecord.results.missing.length">
                <el-tag type="warning">
                    {{ $t('record.missingCount', {count: currentRecord.results.missing.length})}}
                </el-tag>
                <small v-for="phoneNumber in currentRecord.results.missing" style="display: block;">
                    {{ phoneNumber }}
                </small>
            </p>
            <p v-if="isFixed(currentRecord) && currentRecord.results && currentRecord.results.pending && currentRecord.results.pending.length">
                <el-tag type="warning">
                    {{ $t('record.pendingCount', {count: currentRecord.results.pending.length})}}
                </el-tag>
                <small v-for="phoneNumber in currentRecord.results.pending" style="display: block;">
                    {{ phoneNumber }}
                </small>
            </p>
            <el-form ref="recordForm" :model="form" :rules="rules" label-width="150px">
                <el-form-item :label="$t('record.title')" prop="title">
                    <el-input v-model="form.title" :disabled="isFixed(currentRecord)"></el-input>
                </el-form-item>

                <el-form-item :label="$t('sign.name')" prop="aliyun_sms_sign_id">
                    <el-select
                        v-model="form.aliyun_sms_sign_id"
                        :placeholder="$t('table.pleaseChoose')"
                        @focus="fetchSignList"
                        :disabled="isFixed(currentRecord)"
                        :loading="loading">
                        <el-option
                            v-if="!!signList.length"
                            v-for="item in signList"
                            :key="item.id"
                            :label="item.sign_name"
                            :value="item.id">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item :label="$t('template.title')" prop="aliyun_sms_template_id">
                    <el-select
                        v-model="form.aliyun_sms_template_id"
                        :placeholder="$t('table.pleaseChoose')"
                        @focus="fetchTemplateList"
                        :disabled="isFixed(currentRecord)"
                        :loading="loading">
                        <el-option
                            v-if="!!templateList.length"
                            v-for="item in templateList"
                            :key="item.id"
                            :label="item.title"
                            :value="item.id">
                        </el-option>
                    </el-select>

                    <div style="line-height: 1;">
                        <small>
                            {{ getSignContent }}{{ selectedTemplateContent }}
                        </small>
                        <div v-if="getSmsTextCount">{{ getSmsTextCount }}</div>
                    </div>
                </el-form-item>

                <el-form-item :label="$t('record.phoneNumber')" prop="phone_numbers">
                    <el-input type="textarea" v-model="form.phone_numbers" rows="5" :disabled="isFixed(currentRecord)"
                              placeholder="130xxxxxxxx,136xxxxxxxx,139xxxxxxxx"></el-input>
                    <p v-if="this.phoneNumberValidator">
                        <el-tag type="info">{{ this.phoneNumberValidator }}</el-tag>
                    </p>
                </el-form-item>

                <el-form-item :label="$t('record.sendDatetime')" prop="send_datetime">
                    <el-date-picker v-model="form.send_datetime" value-format="yyyy-MM-dd HH:mm:ss"
                                    type="datetime" default-time="12:00:00" :picker-options="pickerOptions"
                                    :placeholder="$t('table.pleaseChoose')" :disabled="isFixed(currentRecord)">
                    </el-date-picker>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogFormVisible = false">{{ $t('table.cancel') }}</el-button>
                <el-button v-if="!isFixed(currentRecord)" type="primary"
                           @click="onRecordSaving">
                    {{ $t('table.confirm') }}
                </el-button>
            </div>
        </el-dialog>

        <el-tooltip placement="top" :content="$t('navbar.backToTop')">
            <back-to-top :visibility-height="300" :back-position="50" transition-name="fade" />
        </el-tooltip>
    </div>
</template>

<script>
    import {getRecordList, storeRecord, updateRecord, deleteRecord} from '@/api/record';
    import {getSignList} from '@/api/sign';
    import {getTemplateList} from '@/api/template';
    import Sticky from '@/components/Sticky';
    import Pagination from '@/components/Pagination';
    import BackToTop from '@/components/BackToTop';
    import {browserHistoryBack} from '@/utils/helper';

    export default {
        components: {Pagination, Sticky, BackToTop},
        data() {
            return {
                list: [],
                loading: null,
                listLoading: false,
                currentRecord: {},
                listQuery: {
                    page: 0,
                    limit: 15,
                    q: {}
                },
                total: 0,
                form: {
                    id: null,
                    title: '',
                    aliyun_sms_sign_id: '',
                    aliyun_sms_template_id: '',
                    phone_numbers: '',
                    send_datetime: '',
                },
                statusList: {
                    0: 'pending',
                    1: 'approved',
                    2: 'rejected'
                },
                rules: {
                    title: [
                        {
                            required: true,
                            message: this.$t('message.pleaseInput', {entity: this.$t('record.title')}),
                            trigger: 'blur'
                        }
                    ],
                    aliyun_sms_sign_id: [
                        {
                            required: true,
                            message: this.$t('message.pleaseChoose', {entity: this.$t('sign.name')}),
                            trigger: 'blur'
                        }
                    ],
                    aliyun_sms_template_id: [
                        {
                            required: true,
                            message: this.$t('message.pleaseChoose', {entity: this.$t('template.title')}),
                            trigger: 'blur'
                        }
                    ],
                    phone_numbers: [
                        {
                            required: true,
                            message: this.$t('message.pleaseInput', {entity: this.$t('record.phoneNumber')}),
                            trigger: 'blur'
                        }
                    ],
                },
                dialogFormVisible: false,
                dialogFormMode: 'create',
                dialogFormTitle: {
                    create: this.$t('table.create'),
                    edit: this.$t('table.edit')
                },
                pickerOptions: {
                    disabledDate(time) {
                        const today = new Date;
                        return time.getTime() < (new Date(today.getFullYear(), today.getMonth(), (today.getDate()))).getTime();
                    }
                },
                signList: [],
                templateList: [],
            }
        },

        computed: {
            phoneNumberValidator() {
                if (!this.form.phone_numbers) {
                    return '';
                } else {
                    if (!/^\d{11}(,\d{11}){0,999}$/.test(this.form.phone_numbers)) {
                        return this.$t('record.formatInvalid');
                    } else {
                        return this.$t('record.phoneNumberCount', {count: this.form.phone_numbers.split(',').length});
                    }
                }
            },
            selectedTemplateContent() {
                for (const template of this.templateList) {
                    if (template.id === this.form.aliyun_sms_template_id) {
                        return template.content;
                    }
                }

                return '';
            },
            getSignContent() {
                if (!this.signList.length || !this.form.aliyun_sms_sign_id || !this.form.aliyun_sms_template_id) {
                    return '';
                }

                const sign = this.signList.find(sign => sign.id === parseInt(this.form.aliyun_sms_sign_id));

                return sign === -1 ? '' : `【${sign.sign_name}】`;
            },
            getSmsTextCount() {
                if (this.selectedTemplateContent && this.getSignContent) {
                    return `${this.$t('common.wordCount')}: ${(this.selectedTemplateContent + this.getSignContent).length}`;
                }

                return '';
            }
        },

        mounted() {
            this.initialize();
        },

        methods: {
            initialize() {
                this.getList();
            },
            openCreateDialog() {
                Promise.all([this.fetchSignList(), this.fetchTemplateList()]);

                this.$nextTick(async () => {
                    this.onCreate();
                });
            },
            openEditDialog(row) {
                Promise.all([this.fetchSignList(), this.fetchTemplateList()]);

                this.$nextTick(async () => {
                    this.onEdit(row);
                });
            },
            async getList(currentQuery = null) {
                this.listLoading = true;

                try {
                    const {data} = await getRecordList(currentQuery || this.listQuery);
                    this.list = data.data;
                    this.total = data.total;
                } catch (error) {
                    this.$message({
                        message: this.$t('message.fetchDataFailed'),
                        type: 'warning'
                    });
                } finally {
                    this.onPageComplete();
                }
            },
            async fetchSignList() {
                if (this.signList.length) {
                    this.onPartialComplete();

                    return this.signList;
                }

                this.loading = true;

                try {
                    const {data} = await getSignList();
                    this.signList = data.filter((sign) => {
                        return sign.status === 1;
                    });

                    if (this.signList.length === 1) {
                        this.form.aliyun_sms_sign_id = this.signList[0].id;
                    }

                    return this.signList;
                } catch (error) {
                    this.$message({
                        message: this.$t('message.fetchDataFailed'),
                        type: 'warning'
                    });
                } finally {
                    this.onPartialComplete();
                }
            },
            async fetchTemplateList() {
                if (this.templateList.length) {
                    this.onPartialComplete();

                    return this.templateList;
                }

                this.loading = true;

                try {
                    const {data} = await getTemplateList();
                    this.templateList = data.filter((template) => {
                        return template.status === 1;
                    });

                    if (this.templateList.length === 1) {
                        this.form.aliyun_sms_template_id = this.templateList[0].id;
                    }

                    return this.templateList;
                } catch (error) {
                    this.$message({
                        message: this.$t('message.fetchDataFailed'),
                        type: 'warning'
                    });
                } finally {
                    this.onPartialComplete();
                }
            },
            onPageComplete() {
                this.listLoading = false;
            },
            onPartialComplete() {
                this.loading = false;
            },
            getStatusType(status) {
                switch (status) {
                    case 0:
                        return 'warning';
                    case 1:
                        return 'success';
                    case 2:
                        return 'danger';
                }
            },
            getStatusText(status) {
                return this.$t(`sign.statusText.${this.statusList[status]}`);
            },
            onBack() {
                browserHistoryBack(this.$router, {name: 'dashboard'});
            },
            onCreate() {
                this.dialogFormVisible = true;
                this.dialogFormMode = 'create';
            },
            onEdit(row) {
                this.form = Object.assign({}, this.form);
                this.dialogFormVisible = true;
                this.dialogFormMode = 'edit';
                this.currentRecord = row;

                this.$nextTick(async () => {
                    this.parseForm(row);
                });
            },
            onDialogClose() {
                this.resetForm();
                this.currentRecord = {};
            },
            resetForm() {
                this.$refs['recordForm'].resetFields();
                this.form.id = null;
            },
            closeDialog() {
                this.dialogFormVisible = false;
            },
            parseForm(row) {
                for (let field in this.form) {
                    if (row[field]) {
                        this.$set(this.form, field, row[field]);
                    }
                }
            },
            async createHandle(action, instance) {
                if (action === 'confirm') {
                    this.listLoading = true;
                    this.errors = {};

                    try {
                        await storeRecord(this.form);

                        this.closeDialog();

                        this.$message({
                            message: this.$t('message.createSuccess'),
                            type: 'success'
                        });

                        await this.getList();
                    } catch (error) {
                        this.$message({
                            message: this.$t('message.createFailed'),
                            type: 'error'
                        });
                    } finally {
                        this.onPageComplete();
                    }
                }
            },
            async updateHandle(action, instance) {
                if (action === 'confirm') {
                    this.listLoading = true;
                    this.errors = {};

                    try {
                        await updateRecord(this.form.id, this.form);

                        this.closeDialog();

                        this.$message({
                            message: this.$t('message.updateSuccess'),
                            type: 'success'
                        });

                        await this.getList();
                    } catch (error) {
                        this.$message({
                            message: this.$t('message.updateFailed'),
                            type: 'error'
                        });
                    } finally {
                        this.onPageComplete();
                    }
                }
            },
            onDelete(id) {
                this.$confirm(this.$t('action.permanentlyDeleteItemText'), this.$t('action.prompt'), {
                    confirmButtonText: this.$t('action.confirm'),
                    cancelButtonText: this.$t('action.cancel'),
                    type: 'warning',
                    callback: this.deleteHandle,
                    id
                });
            },
            onRecordSaving() {
                this.$refs['recordForm'].validate(async (valid) => {
                    if (valid) {
                        const confirmText = !this.form.send_datetime || new Date(this.form.send_datetime) <= Date.now() ?
                            this.$t('record.groupSendingNowConfirm', {
                                count: this.form.phone_numbers.split(',').length
                            }) :
                            this.$t('record.groupSendingConfirm', {
                                datetime: this.form.send_datetime,
                                count: this.form.phone_numbers.split(',').length
                            });

                        this.$confirm(confirmText, this.$t('action.prompt'), {
                            confirmButtonText: this.$t('action.confirm'),
                            cancelButtonText: this.$t('action.cancel'),
                            type: 'warning',
                            callback: this.form.id ? this.updateHandle : this.createHandle
                        });
                    }
                });
            },
            async deleteHandle(action, instance) {
                if (action === 'confirm') {
                    const id = instance.id;
                    this.listLoading = true;

                    try {
                        await deleteRecord(id);

                        await this.getList();

                        this.$message({
                            type: 'success',
                            message: this.$t('message.deleteSuccess')
                        });
                    } catch (e) {
                        this.$message({
                            type: 'warning',
                            message: this.$t('message.deleteFailed')
                        });
                    } finally {
                        this.onPageComplete();
                    }
                }
            },
            setRecordClassName({row}) {
                if (row.deleted_at) {
                    return 'deleted';
                }
                if (row.message === 'OK') {
                    return 'success';
                } else if (row.message && row.message !== 'OK') {
                    return 'failed';
                }
            },
            isFixed(record) {
                return record.message === 'OK';
            },
        }
    }
</script>

<style lang="scss">
    .el-table .el-table__row.deleted {
        background-color: rgba(108, 117, 125, .2);
    }

    .el-table .el-table__row.success {
        background-color: rgba(139, 215, 47, .1);
    }

    .el-table .el-table__row.failed {
        background-color: rgba(220, 53, 69, .1);
    }

    .el-popover {
        max-height: 450px;
        overflow-y: auto;

        ul {
            margin-left: 10px;
            padding-left: 10px;
            list-style-type: none;
        }
    }
</style>
