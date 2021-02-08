<?php
$new_token = 0;
$change_token = falsE;

if ( ! empty( $_POST ) ) {
    foreach ( $_POST as $key => $value ) {
        if($key == 'csc_token' AND $value != get_option('csc_token')) {
            error_log('key: ' . $key  . ' - value: ' . $value);
            $change_token = true;
            $new_token = $value;
        }

        $value = str_replace( [ '\\"', "\\'" ], [ '"', "'" ], $value );
        update_option($key, $value);
    }

    if($change_token) {
        // Save informations
        csc_install($new_token);
    }
}

?>

<form method="post" action="" enctype="multipart/form-data">
    <div class="csc-settings-container">
        <div class="csc-settings-title">
            <div><?php echo __('SmartCheckout - Installation', 'csc_textdomain'); ?></div>
        </div>
        <div class="csc-settings-main">
            <div class="csc-settings-inputtitle"><?php echo __('Indtast dine informationer', 'csc_textdomain'); ?></div>
            <div class="csc-settings-inputs">
                <div class="csc-text-input"><input type="text" value="<?php echo get_option( 'csc_token' ) ?>" name="csc_token" id="csc_token" placeholder="<?php echo __('Indtast installationstoken', 'csc_textdomain'); ?>"></div>
                <div class="csc-text-input"><input type="text" value="<?php echo get_option( 'csc_storename' ) ?>" name="csc_storename" id="csc_storename" placeholder="<?php echo __('Indtast webshoppens navn', 'csc_textdomain'); ?>"></div>
                <div class="csc-text-input">
                    <select name="csc_warehouse" id="csc_warehouse">
                        <option value="normal" <?php echo get_option( 'csc_warehouse') == 'normal' ? 'selected' : '' ?>>Eget varehus</option>
                        <option value="pcn" <?php echo get_option( 'csc_warehouse') == 'pcn' ? 'selected' : '' ?>>PakkecenterNord</option>
                    </select>
                </div>
                <?php if(get_option('csc_warehouse') == 'pcn'): ?>
                    <div class="csc-text-input">
                        <select name="csc_pcn_auto" id="csc_pcn_auto">
                            <option value="">Automatisk håndtering af PakkecenterNord ordre</option>
                            <option value="yes" <?php echo get_option( 'csc_pcn_auto') == 'yes' ? 'selected' : '' ?>>Ja, fremsend automatisk ordre til PCN</option>
                            <option value="no" <?php echo get_option( 'csc_pcn_auto') == 'no' ? 'selected' : '' ?>>Nej, jeg håndtere selv ordrene til PCN</option>
                        </select>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="col1">
                        <input type="submit" value="<?php echo __('Opret forbindelse', 'csc_textdomain'); ?>">
                    </div>
                    <div class="col2">
                        <?php
                        if(get_option('csc_token') == '') {
                            $status_message = 'Not installed';
                            $status_color = '#fb1515';
                        } else {
                            $status_message = 'Installed';
                            $status_color = '#157efb';
                        }
                        ?>
                        <div class="connected" style="background: <?php echo $status_color; ?> !important;"></div><?php echo __($status_message, 'csc_textdomain'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if(get_option('csc_token') != ''): ?>
    <div class="csc-settings-boxes">
        <div>
            <a href="https://account.coolrunner.dk/"><?php echo __('Opret leveringsprodukter', 'csc_textdomain'); ?></a><br>
            Opsætning af leveringsprodukter du ønsker, at tilbyde dine kunder.
        </div>
    </div>

    <div class="csc-settings-boxes">
        <div>
            <a href="?<?php echo CSC::formatUrl( [ 'section' => 'box-sizes' ] ) ?>"><?php echo __('Opret prædefineret kassestørrelser', 'csc_textdomain'); ?></a><br>
            Ved, at gøre dette kan de bespares meget tid ved oprettelse af fragtlabels.
        </div>
    </div>
<?php endif; ?>

<div class="csc-settings-boxes">
    <div>
        <a href="#"><?php echo __('Kontakt kundeservice', 'csc_textdomain'); ?></a><br>
        Har du spørgsmål omkring opsætningen eller omkring CoolRunners produkter, så kan du altid kontakte os.
    </div>
</div>
