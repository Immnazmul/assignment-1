<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="container my-4">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<h3 class="card-title"><?php esc_html_e( 'Enter Password', 'dfcm' ); ?></h3>
					<form method="post">
						<input type="password" name="dfcm_password" class="form-control" placeholder="<?php esc_attr_e( 'Password', 'dfcm' ); ?>" required />
						<button class="btn btn-primary mt-2" type="submit"><?php esc_html_e( 'Submit', 'dfcm' ); ?></button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>