<?php

declare(strict_types=1);

/**
 * +----------------------------------------------------------------------
 * | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 * +----------------------------------------------------------------------
 * | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 * +----------------------------------------------------------------------
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('expense_invoice')) {
            Schema::create('expense_invoice', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('entid')->default(0)->comment('企业ID');
                $table->unsignedInteger('uid')->default(0)->comment('上传/录入人用户ID');
                $table->string('invoice_type', 32)->default('general')->comment('发票类型: special-专票, general-普票, electronic-数电票, quota-定额, toll-通行费, petrol-加油票, other-其他');
                $table->string('invoice_code', 64)->default('')->comment('发票代码(全电/数电发票可为空)');
                $table->string('invoice_number', 64)->default('')->comment('发票号码');
                $table->decimal('amount', 12, 2)->default(0.00)->comment('不含税金额');
                $table->decimal('tax_amount', 12, 2)->default(0.00)->comment('税额');
                $table->decimal('total_amount', 12, 2)->default(0.00)->comment('价税合计总金额');
                $table->date('invoice_date')->nullable()->comment('开票日期');
                $table->string('seller_name', 255)->default('')->comment('销售方名称');
                $table->string('buyer_name', 255)->default('')->comment('购买方名称');
                $table->tinyInteger('status')->default(0)->comment('业务状态: 0-未绑定报销, 1-已绑定报销(审批中), 2-已报销');
                $table->tinyInteger('abnormal_status')->default(0)->comment('异常状态: 0-正常, 1-作废, 2-红冲, 3-假票');
                $table->string('abnormal_reason', 255)->default('')->comment('异常标记原因');
                $table->string('file_path', 500)->default('')->comment('发票原件文件路径');
                $table->string('file_name', 255)->default('')->comment('发票原件文件名');
                $table->string('file_type', 32)->default('')->comment('文件类型: pdf, png, jpg, jpeg等');
                $table->text('mark')->nullable()->comment('事由备注/项目说明');
                $table->unsignedInteger('apply_id')->default(0)->comment('关联报销审批单apply_id');
                $table->timestamp('reimbursed_at')->nullable()->comment('报销归档时间');
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['entid', 'invoice_code', 'invoice_number', 'deleted_at'], 'uk_ent_code_num_del');
                $table->index(['entid', 'uid'], 'idx_ent_uid');
                $table->index(['entid', 'status'], 'idx_ent_status');
                $table->index(['entid', 'abnormal_status'], 'idx_ent_abnormal');
                $table->index(['entid', 'apply_id'], 'idx_ent_apply');
                $table->index(['invoice_number'], 'idx_invoice_number');
                $table->index(['invoice_date'], 'idx_invoice_date');
                $table->comment('进项发票与报销防重表');
            });
        }

        if (! Schema::hasTable('expense_invoice_log')) {
            Schema::create('expense_invoice_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('entid')->default(0)->comment('企业ID');
                $table->unsignedInteger('invoice_id')->default(0)->comment('发票ID');
                $table->unsignedInteger('uid')->default(0)->comment('操作人ID');
                $table->string('action', 64)->default('')->comment('操作类型: create, update, delete, abnormal, bind, unbind, reimburse');
                $table->text('old_data')->nullable()->comment('操作前数据快照');
                $table->text('new_data')->nullable()->comment('操作后数据快照');
                $table->string('remark', 500)->default('')->comment('操作备注说明');
                $table->timestamps();

                $table->index(['entid', 'invoice_id'], 'idx_ent_invoice');
                $table->index(['uid'], 'idx_uid');
                $table->comment('发票操作日志记录表');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_invoice_log');
        Schema::dropIfExists('expense_invoice');
    }
};
