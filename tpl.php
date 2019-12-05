<!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" crossorigin="anonymous">-->
<!--<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" crossorigin="anonymous"></script>-->
<link rel="stylesheet" href="<?php echo $plugin_path;?>css/alikassa.css">

<form name="payment_alikassa" id="alikassaForm" action="javascript:;" method="POST" class="">
    <?php echo $hidden_fields;?>
    <input type="submit" value="<?php _e('Оплатить', 'alikassa');?>">
    <?php echo $cancel_url;?>
</form>

<div class="alikassa" style="text-align: center;">
<?php
if($this->enabledAPI == 'yes') {
    $payment_systems = [
		'Card' => [
			'title' => 'Visa/MasterCard'
		],
		'Qiwi' => [
			'title' => 'Qiwi'
		],
		'YandexMoney' => [
			'title' => 'Яндекс.Деньги'
		],
	];
    if (is_array($payment_systems) && !empty($payment_systems)) {
        ?>
        <button type="button" class="sel-ps-ik btn btn-info btn-lg" data-toggle="modal" data-target="#alikassaModal" style="display: none;">
            Select Payment Method
        </button>
        <div id="alikassaModal" class="ak-modal fade" role="dialog">
            <div class="ak-modal-dialog ak-modal-lg">
                <div class="ak-modal-content" id="plans">
                    <div class="container">
                        <h3>
                            1. <?php _e('Выберите удобный способ оплаты', 'alikassa'); ?><br>
                            2. <?php _e('Укажите валюту', 'alikassa'); ?><br>
                            3. <?php _e('Нажмите &laquo;Оплатить&raquo;', 'alikassa'); ?><br>
                        </h3>
                        <div class="ak-row">
                            <?php foreach ($payment_systems as $ps => $info) { ?>
                                <div class="col-sm-3 text-center payment_system">
                                    <div class="panel panel-warning panel-pricing">
                                        <div class="panel-heading">
                                            <div class="panel-image">
                                                <img src="<?php echo $image_path . $ps; ?>.png"
                                                     alt="<?php echo $info['title']; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="input-group">
                                                <div class="radioBtn btn-group">
                                                    <?php foreach ($info['currency'] as $currency => $currencyAlias) { ?>
                                                        <a class="btn btn-primary btn-sm notActive"
                                                           data-toggle="fun"
                                                           data-title="<?php echo $currencyAlias; ?>"><?php echo $currency; ?></a>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel-footer">
                                            <a class="btn btn-lg btn-block btn-success ak-payment-confirmation"
                                               data-title="<?php echo $ps; ?>"
                                               href="#"><?php _e('Оплатить через', 'alikassa'); ?><br>
                                                <strong><?php echo $info['title']; ?></strong>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } 
	else
        echo $payment_systems;
}
?>
</div>
<?php /*wp_enqueue_script('', '/wp-content/plugins/ak-gateway/js/alikassa.js', [], false, true);*/ ?>