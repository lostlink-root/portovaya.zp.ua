<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-salesdrive" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <?php if ($success) { ?>
        <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-salesdrive" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-name"><?php echo $entry_domain; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="module_salesdrive_domain" value="<?php echo $module_salesdrive_domain; ?>" placeholder="<?php echo $entry_domain; ?>" id="input-domain" class="form-control" />
                            <?php if ($error_domain) { ?>
                            <div class="text-danger"><?php echo $error_domain; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-limit"><?php echo $entry_key; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="module_salesdrive_key" value="<?php echo $module_salesdrive_key; ?>" placeholder="<?php echo $entry_key; ?>" id="input-key" class="form-control" />
                            <?php if ($error_key) { ?>
                            <div class="text-danger"><?php echo $error_key; ?></div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                        <div class="col-sm-10">
                            <select name="module_salesdrive_status" id="input-status" class="form-control">
                                <?php if ($module_salesdrive_status) { ?>
                                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                                <option value="0"><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value="1"><?php echo $text_enabled; ?></option>
                                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($module_salesdrive_domain) { ?>
                    <!--div class="text-center"><a href="<?php echo $synchronize; ?>" data-toggle="tooltip" title="<?php echo $button_synchronize; ?>" class="btn btn-default"><i class="fa fa-refresh"></i> Стара <?php echo $button_synchronize; ?></a></div-->

                   <div class="text-center"><div class="btn btn-default" id="fca-import-order"><i class="fa fa-refresh"></i> <?php echo $button_synchronize; ?></div></div>
					<script src="view/javascript/salesdrive/sync.js"></script>
					<link type="text/css" href="view/javascript/salesdrive/sync.css" rel="stylesheet" media="screen" />
					<div>&nbsp;</div>
					<div id="importProductsUrl" style="display: none"><?php echo $synchronize; ?></div>
					<div class="fca_ajax_result" style="display: none">Экспортировано товаров: <span id="currentOffset">0</span>. Создано товаров с учетом вариаций: <span id="variationCount">0</span>. Время выполнения: <span id="timeElapsed">0</span>. <span id="sd-finish" style="display: none">ЗАВЕРШЕНО!</span></div>
					<div id="fc_api_project_box">
						<div class="fca_preloader">
							<div>Товары и категории экспортируются. Не закрывайте браузер до завершения экспорта.</div>
							<div class="lds-default">
								<div></div>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
								<div></div>
							</div>
						</div>
					</div>
                                                                              
                                        
                    <?php } ?>
                    <h3 style="margin-top: 20px;"><?php echo $heading_stock_import; ?></h3>
                    <div class="form-group">
                            <label class="col-sm-2 control-label" for="input-feed"><?php echo $entry_feed; ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="module_salesdrive_feed" value="<?php echo $module_salesdrive_feed; ?>" placeholder="<?php echo $entry_feed; ?>" id="input-feed" class="form-control" />
                            </div>
                    </div>
                    <div class="form-group">
                            <label class="col-sm-2 control-label" for="input_gen"><?php echo $entry_gen; ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="module_salesdrive_gen" value="<?php echo $module_salesdrive_gen; ?>" id="input_gen" class="form-control" disabled />
                            </div>
                    </div>
                    <div class="form-group">
                            <label class="col-sm-2 control-label" for="input_gen"><?php echo $entry_cron; ?></label>
                            <div class="col-sm-10">
                                <input type="text" name="module_salesdrive_gen" value="curl <?php echo $module_salesdrive_gen; ?>" id="input_gen" class="form-control" disabled />
                            </div>
                    </div>
                </form>
            </div>            
        </div>
    </div>
</div>
<?php echo $footer; ?>
