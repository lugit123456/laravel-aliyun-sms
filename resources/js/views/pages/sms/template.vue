<template>
    <div class="inner-container">
        <sticky class-name="sub-navbar" style="margin-bottom: 1rem;" :zIndex="5">
            <el-button type="success" @click="onCreate">{{ $t('table.add') }}</el-button>
            <el-button @click="onBack">{{ $t('table.back') }}</el-button>
        </sticky>

        <el-table
            :data="list"
            border
            row-key="id"
            ref="listTable"
            v-show="list"
            v-loading="listLoading"
            style="width: 100%">
            <el-table-column
                :label="$t('template.title')">
                <template slot-scope="{row}">
                    <del v-if="row.deleted_at">{{ row.title }}</del>
                    <span v-else>{{ row.title }}</span>
                </template>
            </el-table-column>
            <el-table-column
                :label="$t('template.content')">
                <template slot-scope="{row}">
                    <del v-if="row.deleted_at">{{ row.content }}</del>
                    <span v-else>{{ row.content }}</span>
                </template>
            </el-table-column>
            <el-table-column
                :label="$t('common.createdAt')">
                <template slot-scope="{row}">
                    <del v-if="row.deleted_at">{{ row.created_at }}</del>
                    <span v-else>{{ row.created_at }}</span>
                </template>
            </el-table-column>
            <el-table-column
                :label="$t('common.status')"
                width="120">
                <template slot-scope="{row}">
                    <el-tag :type="getStatusType(row.status)">{{ getStatusText(row.status) }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column
                fixed="right"
                :label="$t('table.actions')"
                width="140">
                <template slot-scope="{row}">
                    <el-button type="text" size="small" @click="openEditDialog(row)">
                        {{ $t('table.edit') }}
                    </el-button>
                    <el-button type="text" size="small" v-if="row.status !== 0" @click="onDelete(row.id)">
                        {{ $t('table.delete')}}
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <el-dialog
            :title="dialogFormTitle[dialogFormMode]"
            :visible.sync="dialogFormVisible"
            :close-on-click-modal="false"
            @close="onDialogClose">
            <el-form ref="templateForm" :model="form" :rules="rules" label-width="150px">
                <el-form-item :label="$t('template.title')" prop="title">
                    <el-input v-model="form.title"></el-input>
                </el-form-item>

                <el-form-item :label="$t('template.content')" prop="content">
                    <el-input type="textarea" v-model="form.content" rows="5"
                              placeholder="变量格式：${code}；
 示例：您的验证码为：${code}，该验证码 5 分钟内有效，请勿泄漏于他人。"
                              :disabled="this.currentTemplate && this.currentTemplate.status !== 2"></el-input>
                </el-form-item>

                <el-form-item :label="$t('template.type')" prop="type">
                    <el-select v-model="form.type" :placeholder="$t('table.pleaseChoose')" style="width: 100%"
                               :disabled="this.currentTemplate && this.currentTemplate.status !== 2">
                        <el-option
                            v-for="(item, index) in types"
                            :key="index"
                            :label="$t(`template.typeText.${item}`)"
                            :value="index">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item :label="$t('template.remark')" prop="remark">
                    <el-input type="textarea" v-model="form.remark"
                              :disabled="this.currentTemplate && this.currentTemplate.status !== 2"></el-input>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogFormVisible = false">{{ $t('table.cancel') }}</el-button>
                <el-button type="primary" @click="dialogFormMode === 'create' ? createHandle() : updateHandle()">
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
    import {getTemplateList, storeTemplate, updateTemplate, deleteTemplate} from '@/api/template';
    import Sticky from '@/components/Sticky';
    import BackToTop from '@/components/BackToTop';
    import {browserHistoryBack} from '@/utils/helper';

    export default {
        components: {Sticky, BackToTop},
        data() {
            return {
                list: [],
                loading: null,
                listLoading: false,
                currentTemplate: null,
                form: {
                    id: null,
                    title: '',
                    content: '',
                    type: '0',
                    remark: '',
                },
                types: {
                    0: 'verificationCode',
                    1: 'notification',
                    2: 'promotion',
                    3: 'outOfMainland',
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
                            message: this.$t('message.pleaseInput', {entity: this.$t('template.title')}),
                            trigger: 'blur'
                        }
                    ],
                    content: [
                        {
                            required: true,
                            message: this.$t('message.pleaseInput', {entity: this.$t('template.content')}),
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
                this.onCreate();
            },
            openEditDialog(row) {
                this.currentTemplate = row;
                this.onEdit(row);
            },
            async getList() {
                this.listLoading = this.$loading();

                try {
                    const {data} = await getTemplateList();
                    this.list = data;

                    this.onPageComplete();
                } catch (error) {
                    this.$message({
                        message: this.$t('message.fetchDataFailed'),
                        type: 'warning'
                    });

                    this.onPageComplete();
                }
            },
            onPageComplete() {
                if (typeof this.listLoading['close'] === 'function') {
                    this.listLoading.close();
                }
                this.listLoading = false;
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
                return this.$t(`template.statusText.${this.statusList[status]}`);
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

                this.$nextTick(async () => {
                    this.parseForm(row);
                });
            },
            onDialogClose() {
                this.currentTemplate = null;
                this.resetForm();
            },
            resetForm() {
                this.$refs['templateForm'].resetFields();
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
            createHandle() {
                this.$refs['templateForm'].validate(async (valid) => {
                    if (valid) {
                        this.listLoading = this.$loading();
                        this.errors = {};

                        try {
                            await storeTemplate(this.form);

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
                });
            },
            updateHandle(id) {
                this.$refs['templateForm'].validate(async (valid) => {
                    if (valid) {
                        this.listLoading = this.$loading();
                        this.errors = {};

                        try {
                            await updateTemplate(this.form.id, this.form);

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
                });
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
            async deleteHandle(action, instance) {
                if (action === 'confirm') {
                    const id = instance.id;
                    this.listLoading = this.$loading();

                    try {
                        await deleteTemplate(id);

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
        }
    }
</script>
