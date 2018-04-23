<div class="wrap">
    <h1>{{ __( 'Central Authentication Service (CAS)', 'pressbooks-cas-sso') }}</h1>
    <form method="POST" action="{{ $form_url }}" method="post">
        {!! wp_nonce_field( 'pb-cas-sso' ) !!}
        <table class="form-table">
            <tr>
                <th><label for="server_version">{{ __('CAS Version', 'pressbooks-cas-sso') }}</label></th>
                <td><select name="server_version" id="server_version">
                        <option value="CAS_VERSION_3_0" {!! selected( $options['server_version'], 'CAS_VERSION_3_0' ) !!} >3</option>
                        <option value="CAS_VERSION_2_0" {!! selected( $options['server_version'], 'CAS_VERSION_2_0' ) !!} >2</option>
                        <option value="CAS_VERSION_1_0" {!! selected( $options['server_version'], 'CAS_VERSION_1_0' ) !!} >1</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="server_hostname">{{ __('Server Hostname', 'pressbooks-cas-sso') }}</label></th>
                <td><input name="server_hostname" id="server_hostname" type="text" value="{{ $options['server_hostname'] }}" class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="server_port">{{ __('Server Port', 'pressbooks-cas-sso') }}</label></th>
                <td><input name="server_port" id="server_port" type="text" value="{{ $options['server_port'] }}" class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="server_path">{{ __('Server Path', 'pressbooks-cas-sso') }}</label></th>
                <td><input name="server_path" id="server_path" type="text" value="{{ $options['server_path'] }}" class="regular-text"/></td>
            </tr>
        </table>
        <h2>{{ __('Optional Information', 'pressbooks-cas-sso') }}</h2>
        <table class="form-table">
            <tr>
                <th><label for="email_suffix">{{ __('E-mail Suffix', 'pressbooks-cas-sso') }}</label></th>
                <td>
                    <input name="email_suffix" id="email_suffix" type="text" value="{{ $options['email_suffix'] }}" class="regular-text"/>
                    <p>
                        <em>{{ __("If your users' emails are based on their NetIDs (ex: NetID@university.edu), specify your institution's email suffix here to generate your users' email adresses.", 'pressbooks-cas-sso') }}</em>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="forced_redirection">{{ __('Hide the Pressbooks Login Page', 'pressbooks-cas-sso') }}</label></th>
                <td>
                    <input name="forced_redirection" id="forced_redirection" type="checkbox" value="1" {!! checked( $options['forced_redirection'] ) !!}/>
                </td>
            </tr>
        </table>
		<?php submit_button(); ?>
    </form>
</div>