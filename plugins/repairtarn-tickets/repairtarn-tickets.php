<?php
/**
 * Plugin Name: RepairTarn Tickets
 * Description: Gestion des tickets de réparation (CPT, taxonomie, métadonnées).
 * Version: 1.0
 * Author: Ton Nom
 */

// Sécurité de base.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enregistrement du Custom Post Type "Ticket".
 */
function rt_register_ticket_cpt() {

    $labels = array(
        'name'                  => 'Tickets',
        'singular_name'         => 'Ticket',
        'menu_name'             => 'Tickets',
        'name_admin_bar'        => 'Ticket',
        'add_new'               => 'Ajouter',
        'add_new_item'          => 'Ajouter un ticket',
        'edit_item'             => 'Modifier le ticket',
        'new_item'              => 'Nouveau ticket',
        'view_item'             => 'Voir le ticket',
        'search_items'          => 'Rechercher des tickets',
        'not_found'             => 'Aucun ticket trouvé',
        'not_found_in_trash'    => 'Aucun ticket dans la corbeille',
        'all_items'             => 'Tous les tickets',
    );

    $args = array(
        'label'                 => 'Tickets',
        'labels'                => $labels,
        'public'                => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-tickets',
        'supports'              => array( 'title', 'editor' ), // Titre = appareil, contenu = description longue.
        'has_archive'           => true,
        'rewrite'               => array( 'slug' => 'tickets' ),
        'show_in_rest'          => true, // Pour Gutenberg.
    );

    register_post_type( 'rt_ticket', $args );
}
add_action( 'init', 'rt_register_ticket_cpt' );

/**
 * Taxonomie "Statut du ticket" (Ouvert, En cours, Annulé, Résolu).
 */
function rt_register_ticket_status_taxonomy() {

    $labels = array(
        'name'              => 'Statuts de ticket',
        'singular_name'     => 'Statut de ticket',
        'search_items'      => 'Rechercher des statuts',
        'all_items'         => 'Tous les statuts',
        'edit_item'         => 'Modifier le statut',
        'update_item'       => 'Mettre à jour le statut',
        'add_new_item'      => 'Ajouter un nouveau statut',
        'new_item_name'     => 'Nouveau statut',
        'menu_name'         => 'Statuts de ticket',
    );

    $args = array(
        'labels'            => $labels,
        'public'            => true,
        'hierarchical'      => false, // Comme des étiquettes.
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'ticket-status' ),
    );

    register_taxonomy( 'rt_ticket_status', array( 'rt_ticket' ), $args );
}
add_action( 'init', 'rt_register_ticket_status_taxonomy' );


/**
 * Ajout de la meta box "Détails du ticket".
 * - Technicien
 * - Appareil (si tu veux différent du titre)
 * - Description courte
 * - Date de dépôt
 * - Date de prise en charge
 * - Priorité
 */
function rt_add_ticket_metaboxes() {
    add_meta_box(
        'rt_ticket_details',
        'Détails du ticket',
        'rt_ticket_details_callback',
        'rt_ticket',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'rt_add_ticket_metaboxes' );

/**
 * Callback de la meta box.
 */
function rt_ticket_details_callback( $post ) {

    // Valeurs existantes.
    $technician   = get_post_meta( $post->ID, '_rt_technician', true );
    $device       = get_post_meta( $post->ID, '_rt_device', true );
    $short_desc   = get_post_meta( $post->ID, '_rt_short_desc', true );
    $deposit_date = get_post_meta( $post->ID, '_rt_deposit_date', true );
    $start_date   = get_post_meta( $post->ID, '_rt_start_date', true );
    $priority     = get_post_meta( $post->ID, '_rt_priority', true );

    // Nonce pour sécuriser la sauvegarde.
    wp_nonce_field( 'rt_save_ticket_details', 'rt_ticket_details_nonce' );
    ?>
    <p>
        <label for="rt_technician"><strong>Technicien</strong></label><br />
        <input type="text" id="rt_technician" name="rt_technician" class="widefat"
               value="<?php echo esc_attr( $technician ); ?>" placeholder="Ex : Marie" />
    </p>

    <p>
        <label for="rt_device"><strong>Appareil</strong></label><br />
        <input type="text" id="rt_device" name="rt_device" class="widefat"
               value="<?php echo esc_attr( $device ); ?>" placeholder="Ex : iPhone 14 Pro" />
        <em>Tu peux aussi utiliser le titre du ticket pour le nom de l’appareil.</em>
    </p>

    <p>
        <label for="rt_short_desc"><strong>Description courte</strong></label><br />
        <textarea id="rt_short_desc" name="rt_short_desc" class="widefat" rows="3"
                  placeholder="Ex : Écran cassé + batterie gonflée"><?php
            echo esc_textarea( $short_desc );
        ?></textarea>
    </p>

    <p>
        <label for="rt_deposit_date"><strong>Date de dépôt</strong></label><br />
        <input type="date" id="rt_deposit_date" name="rt_deposit_date"
               value="<?php echo esc_attr( $deposit_date ); ?>" />
    </p>

    <p>
        <label for="rt_start_date"><strong>Date de prise en charge</strong></label><br />
        <input type="date" id="rt_start_date" name="rt_start_date"
               value="<?php echo esc_attr( $start_date ); ?>" />
    </p>

    <p>
        <label for="rt_priority"><strong>Priorité</strong></label><br />
        <select id="rt_priority" name="rt_priority">
            <option value="">Choisir…</option>
            <option value="basse"   <?php selected( $priority, 'basse' ); ?>>Basse</option>
            <option value="normale" <?php selected( $priority, 'normale' ); ?>>Normale</option>
            <option value="haute"   <?php selected( $priority, 'haute' ); ?>>Haute</option>
        </select>
    </p>
    <?php
}

/**
 * Sauvegarde des métadonnées de la meta box.
 */
function rt_save_ticket_meta( $post_id ) {

    // Vérif nonce.
    if ( ! isset( $_POST['rt_ticket_details_nonce'] ) ||
         ! wp_verify_nonce( $_POST['rt_ticket_details_nonce'], 'rt_save_ticket_details' ) ) {
        return;
    }

    // Éviter auto-save, etc.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['post_type'] ) && 'rt_ticket' === $_POST['post_type'] ) {
        // Droits de l’utilisateur.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }

    // Sauvegarder chaque champ si présent.
    $fields = array(
        'rt_technician'   => '_rt_technician',
        'rt_device'       => '_rt_device',
        'rt_short_desc'   => '_rt_short_desc',
        'rt_deposit_date' => '_rt_deposit_date',
        'rt_start_date'   => '_rt_start_date',
        'rt_priority'     => '_rt_priority',
    );

    foreach ( $fields as $form_key => $meta_key ) {
        if ( isset( $_POST[ $form_key ] ) ) {
            $value = sanitize_text_field( wp_unslash( $_POST[ $form_key ] ) );
            update_post_meta( $post_id, $meta_key, $value );
        }
    }
}
add_action( 'save_post_rt_ticket', 'rt_save_ticket_meta' );

/**
 * Shortcode [rt_ticket id="123"] pour afficher une carte de ticket.
 */
function rt_ticket_card_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'id' => 0,
        ),
        $atts,
        'rt_ticket'
    );

    $ticket_id = intval( $atts['id'] );
    if ( ! $ticket_id ) {
        return '';
    }

    $post = get_post( $ticket_id );
    if ( ! $post || $post->post_type !== 'rt_ticket' ) {
        return '';
    }

    // Métadonnées.
    $technician   = get_post_meta( $ticket_id, '_rt_technician', true );
    $device       = get_post_meta( $ticket_id, '_rt_device', true );
    $short_desc   = get_post_meta( $ticket_id, '_rt_short_desc', true );
    $deposit_date = get_post_meta( $ticket_id, '_rt_deposit_date', true );
    $start_date   = get_post_meta( $ticket_id, '_rt_start_date', true );

    // Statut (taxonomie) – on prend le premier terme trouvé.
    $status_terms = get_the_terms( $ticket_id, 'rt_ticket_status' );
    $status_name  = '';
    $status_slug  = '';

    if ( ! is_wp_error( $status_terms ) && ! empty( $status_terms ) ) {
        $status_name = $status_terms[0]->name;
        $status_slug = $status_terms[0]->slug; // ex : ouvert, en-cours, annule, resolu
    }

    // Classes CSS pour le badge de statut (on utilisera le slug).
    $status_class = $status_slug ? 'rt-status-badge rt-status-' . esc_attr( $status_slug ) : 'rt-status-badge';

    // Nom / avatar : pour le moment avatar générique (on pourra connecter plus tard à l’utilisateur).
    ob_start();
    ?>
    <article class="rt-ticket-card">
        <div class="rt-ticket-header">
            <div class="rt-ticket-avatar">
                <!-- Avatar générique, à remplacer plus tard par une vraie image -->
                <div class="rt-avatar-placeholder"></div>
            </div>
            <div class="rt-ticket-header-text">
                <h3 class="rt-ticket-technician-name">
                    <?php echo esc_html( $technician ? $technician : 'Technicien inconnu' ); ?>
                </h3>
                <p class="rt-ticket-technician-sub">
                    Technicien : <?php echo esc_html( $technician ? $technician : '—' ); ?>
                </p>
            </div>
        </div>

        <?php if ( $status_name ) : ?>
            <div class="<?php echo $status_class; ?>">
                <?php echo esc_html( $status_name ); ?>
            </div>
        <?php endif; ?>

        <div class="rt-ticket-body">
            <h4 class="rt-ticket-device">
                <?php echo esc_html( $device ? $device : get_the_title( $ticket_id ) ); ?>
            </h4>
            <?php if ( $short_desc ) : ?>
                <p class="rt-ticket-short-desc">
                    <?php echo esc_html( $short_desc ); ?>
                </p>
            <?php endif; ?>

            <p class="rt-ticket-date rt-ticket-date-deposit">
                Dépôt : <span><?php echo $deposit_date ? esc_html( date_i18n( 'd/m/Y', strtotime( $deposit_date ) ) ) : '—'; ?></span>
            </p>
            <p class="rt-ticket-date rt-ticket-date-start">
                Prise en charge : <span><?php echo $start_date ? esc_html( date_i18n( 'd/m/Y', strtotime( $start_date ) ) ) : '—'; ?></span>
            </p>
        </div>

        <div class="rt-ticket-footer">
            <a href="<?php echo esc_url( get_permalink( $ticket_id ) ); ?>" class="rt-ticket-button">
                Voir le ticket
            </a>
        </div>
    </article>
    <?php
    return ob_get_clean();
}
add_shortcode( 'rt_ticket', 'rt_ticket_card_shortcode' );

