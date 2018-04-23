<div class="wrap">
    <h1>{{ __( 'Central Authentication Service (CAS)', 'pressbooks-cas-sso') }}</h1>
    <form method="POST" action="{{ $form_url }}" method="post">
        {!! wp_nonce_field( 'pb-cas-sso' ) !!}
        <table class="form-table">
            <tr>
                <th><label for="server_version">CAS Version</label></th>
                <td><select name="server_version" id="server_version">
                        <option value="CAS_VERSION_3_0" {!! selected( $options['server_version'], 'CAS_VERSION_3_0' ) !!} >3</option>
                        <option value="CAS_VERSION_2_0" {!! selected( $options['server_version'], 'CAS_VERSION_2_0' ) !!} >2</option>
                        <option value="CAS_VERSION_1_0" {!! selected( $options['server_version'], 'CAS_VERSION_1_0' ) !!} >1</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="server_hostname">Server Hostname</label></th>
                <td><input name="server_hostname" id="server_hostname" type="text" value="{{ $options['server_hostname'] }}" class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="server_port">Server Port</label></th>
                <td><input name="server_port" id="server_port" type="text" value="{{ $options['server_port'] }}" class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="server_path">Server Path</label></th>
                <td><input name="server_path" id="server_path" type="text" value="{{ $options['server_path'] }}" class="regular-text"/></td>
            </tr>
        </table>
        <h2>Extras</h2>
        <table class="form-table">
            <tr>
                <th><label for="email_suffix">E-mail Suffix</label></th>
                <td>
                    <input name="email_suffix" id="email_suffix" type="text" value="{{ $options['email_suffix'] }}" class="regular-text"/>
                    <p><em>If you know that the owner of the CAS authentication service issues email addresses based on their netids, you can predict your users' emails here.</em></p>
                </td>
            </tr>
            <tr>
                <th><label for="forced_redirection">Forced Redirection</label></th>
                <td>
                    <input name="forced_redirection" id="forced_redirection" type="checkbox" value="1" {!! checked( $options['forced_redirection'] ) !!}/>
                    <p><em>Select this if you want to hide Pressbooks login stuff.</em></p>
                </td>
            </tr>
        </table>
		<?php submit_button(); ?>
    </form>
</div>