<?php
if ( ! empty( $_POST ) ) {
    foreach ( $_POST as $key => $value ) {
        $value = str_replace( [ '\\"', "\\'" ], [ '"', "'" ], $value );
        update_option($key, $value);
    }

    if(get_option('last_product_save') == '') {
        // Connect to CoolRunner
        $smart_checkout = new \SmartCheckoutSDK\Connect();
        $smart_checkout->connect($value);

        // Save when we save products last
        update_option('last_product_save', date('d-m-Y H:i:s'));
    }
}

?>

<form method="post" action="" enctype="multipart/form-data">
    <div class="csc-settings-container">
        <div class="csc-settings-title">
            <div><?php echo __('SmartCheckout - Installation', CSC_TEXTDOMAIN); ?></div>
        </div>
        <div class="csc-settings-main">
            <div class="csc-settings-inputtitle"><?php echo __('Indtast dine informationer', CSC_TEXTDOMAIN); ?></div>
            <div class="csc-settings-inputs">
                <div class="csc-text-input"><input type="text" value="<?php echo get_option( 'csc_token' ) ?>" name="csc_token" id="csc_token" placeholder="<?php echo __('Indtast installationstoken', CSC_TEXTDOMAIN); ?>"></div>
                <div class="csc-text-input"><input type="text" value="<?php echo get_option( 'csc_storename' ) ?>" name="csc_storename" id="csc_storename" placeholder="<?php echo __('Indtast webshoppens navn', CSC_TEXTDOMAIN); ?>"></div>
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
                        <input type="submit" value="<?php echo __('Opret forbindelse', CSC_TEXTDOMAIN); ?>">
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
                        <div class="connected" style="background: <?php echo $status_color; ?> !important;"></div><?php echo __($status_message, CSC_TEXTDOMAIN); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if(get_option('csc_token') != ''): ?>
    <div class="csc-settings-boxes">
        <div>
            <a href="https://coolrunner.dk"><?php echo __('Opret leveringsprodukter', CSC_TEXTDOMAIN); ?></a><br>
            Opsætning af leveringsprodukter du ønsker, at tilbyde dine kunder.
        </div>
    </div>

    <div class="csc-settings-boxes">
        <div>
            <a href="?<?php echo CSC::formatUrl( [ 'section' => 'box-sizes' ] ) ?>"><?php echo __('Opret prædefineret kassestørrelser', CSC_TEXTDOMAIN); ?></a><br>
            Ved, at gøre dette kan de bespares meget tid ved oprettelse af fragtlabels.
        </div>
    </div>
<?php endif; ?>

<div class="csc-settings-boxes">
    <div>
        <a href="#"><?php echo __('Kontakt kundeservice', CSC_TEXTDOMAIN); ?></a><br>
        Har du spørgsmål omkring opsætningen eller omkring CoolRunners produkter, så kan du altid kontakte os.
    </div>
</div>
