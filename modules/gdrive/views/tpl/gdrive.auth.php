<div id="bupGDriveWrapper">
    <div>
        <table class="bupTable100per">
            <tr>
                <td>Client ID</td>
                <td style="padding: 5px 1px;"><?php echo htmlBup::text('gdrive_client_id', array('attrs' => 'class="inputField100per"', 'value' => $credentials['clientId']))?></td>
            </tr>
            <tr>
                <td >Client secret</td>
                <td style="padding: 5px 1px;"><?php echo htmlBup::text('gdrive_client_secret', array('attrs' => 'class="inputField100per"', 'value' => $credentials['clientSecret']))?></td>
            </tr>
        </table>
    </div>
    <div id="bupGDriveAlerts"></div>
    <button id="bupGDriveCredentials" class="button button-primary button-large gDriveAuthenticate"><?php _e('Authenticate', BUP_LANG_CODE); ?></button>
    <?php
    if(!empty($errors) && is_array($errors)):
        foreach($errors as $error): ?>
        <p class="bupErrorMsg"><?php echo $error; ?></p>
    <?php
        endforeach;
    endif;
    ?>
</div>