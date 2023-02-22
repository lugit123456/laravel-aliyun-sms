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
                :label="$t('sign.name')">
                <template slot-scope="{row}">
                    <del v-if="row.deleted_at">{{ row.sign_name }}</del>
                    <span v-else>{{ row.sign_name }}</span>
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
                    <el-button type="text" size="small" v-if="row.status === 0" @click="onEdit(row)">
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
            <el-form ref="signForm" :model="form" :rules="rules" label-width="150px">
                <el-form-item :label="$t('sign.name')" prop="sign_name">
                    <el-input v-model="form.sign_name" :disabled="this.dialogFormMode === 'edit'"></el-input>
                </el-form-item>

                <el-form-item :label="$t('sign.source')" prop="sign_source">
                    <el-select v-model="form.sign_source" :placeholder="$t('table.pleaseChoose')" style="width: 100%">
                        <el-option
                            v-for="(item, index) in sources"
                            :key="index"
                            :label="item"
                            :value="index">
                        </el-option>
                    </el-select>
                </el-form-item>

                <el-form-item :label="$t('sign.remark')" prop="remark">
                    <el-input type="textarea" v-model="form.remark"></el-input>
                </el-form-item>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogFormVisible = false">{{ $t('table.cancel') }}</el-button>
                <el-button type="primary" @click="dialogFormMode === 'create' ? createHandle() : updateHandle()">{{
                    $t('table.confirm') }}
                </el-button>
            </div>
        </el-dialog>

        <el-tooltip placement="top" :content="$t('navbar.backToTop')">
            <back-to-top :visibility-height="300" :back-position="50" transition-name="fade" />
        </el-tooltip>
    </div>
</template>

<script>
    import {getSignList, storeSign, updateSign, deleteSign} from '@/api/sign';
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
                currentLesson: null,
                form: {
                    id: null,
                    sign_name: '',
                    sign_source: '0',
                    remark: '',
                },
                sources: {
                    0: '企事业单位的全称或简称',
                    1: '工信部备案网站的全称或简称',
                    2: 'APP应用的全称或简称',
                    3: '公众号或小程序的全称或简称',
                    4: '电商平台店铺名的全称或简称',
                    5: '商标名的全称或简称',
                },
                statusList: {
                    0: 'pending',
                    1: 'approved',
                    2: 'rejected'
                },
                rules: {
                    sign_name: [
                        {
                            required: true,
                            message: this.$t('message.pleaseInput', {entity: this.$t('sign.name')}),
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
            async getList() {
                this.listLoading = this.$loading();

                try {
                    const {data} = await getSignList();
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

                this.$nextTick(async () => {
                    this.parseForm(row);
                });
            },
            onDialogClose() {
                this.resetForm();
            },
            resetForm() {
                this.$refs['signForm'].resetFields();
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
                this.$refs['signForm'].validate(async (valid) => {
                    if (valid) {
                        this.listLoading = this.$loading();
                        this.errors = {};

                        try {
                            await storeSign(this.form);

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
                this.$refs['signForm'].validate(async (valid) => {
                    if (valid) {
                        this.listLoading = this.$loading();
                        this.errors = {};

                        try {
                            await updateSign(this.form.id, this.form);

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
                        await deleteSign(id);

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
