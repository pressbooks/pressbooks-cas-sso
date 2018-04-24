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
            <tr>
                <th><label for="provision">{{ __('If the CAS user does not have a Pressbooks account', 'pressbooks-cas-sso') }}</label></th>
                <td><select name="provision" id="provision">
                        <option value="refuse" {!! selected( $options['provision'], 'refuse' ) !!} >{{ __('Refuse Access', 'pressbooks-cas-sso') }}</option>
                        <option value="create" {!! selected( $options['provision'], 'create' ) !!} >{{ __('Add New User', 'pressbooks-cas-sso') }}</option>
                    </select>
                </td>
            </tr>
        </table>
        <h2>{{ __('Optional Information', 'pressbooks-cas-sso') }}</h2>
        <table class="form-table">
            <tr>
                <th><label for="email_domain">{{ __('Email Domain', 'pressbooks-cas-sso') }}</label></th>
                <td>
                    <input name="email_domain" id="email_domain" type="text" value="{{ $options['email_domain'] }}" class="regular-text"/>
                    <p>
                        <em>{{ __("If your users' emails are based on their NetIDs (ex: NetID@university.edu), specify your institution's email domain here to generate your users' email adresses.", 'pressbooks-cas-sso') }}</em>
                    </p>
                </td>
            </tr>
            <tr>
                <th>{{ __(' Bypass', 'pressbooks-cas-sso') }}</th>
                <td><label><input name="bypass" id="bypass" type="checkbox"
                                  value="1" {!! checked( $options['bypass'] ) !!}/> {!!
                                  sprintf( __('Bypass the "Limited Email Registrations" and "Banned Email Domains" lists under <a href="%s">Network Settings</a>.', 'pressbooks-cas-sso') ,'settings.php' )
                                   !!}
                    </label></td>
            </tr>
            <tr>
                <th>{{ __(' Forced Redirection', 'pressbooks-cas-sso') }}</th>
                <td>
                    <label><input name="forced_redirection" id="forced_redirection" type="checkbox"
                                  value="1" {!! checked( $options['forced_redirection'] ) !!}/> {{ __('Hide the Pressbooks login page.', 'pressbooks-cas-sso') }}</label>
                </td>
            </tr>
        </table>
        {!! get_submit_button() !!}
    </form>
</div>