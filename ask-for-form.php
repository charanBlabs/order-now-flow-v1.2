<section class="bdgs-asq-form-section" id="bdgs-asq-form-quotation">
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-12 nopad">
				<div class="right_quotation" style="padding:20px 25px;background-color:#fff;">

					<!-- Header -->
					<div id="leadForm">
						<h2 style="font-size:24px;font-weight:600;margin-bottom:10px;">Get started. It’s 100% Risk-free.</h2>
						<p style="font-weight:300;font-size:14px;color:#374151;margin-bottom:20px;">
							If developers can't achieve the work, you get 100% refund.
						</p>

						<!-- Form -->
						<form class="requirements_form" method="POST" enctype="multipart/form-data">

							<div class="row">
								<!-- Name -->
								<div class="col-md-6">
									<div class="form-group">
										<label for="lead_name">Name *</label>
										<div class="input-group">
											<span class="input-group-addon"><i class="fa fa-user"></i></span>
											<input type="text" name="lead_name" id="lead_name" class="form-control"
												placeholder="John Smith (Example)" required>
										</div>
									</div>
								</div>

								<!-- Email -->
								<div class="col-md-6">
									<div class="form-group">
										<label for="lead_email">Email *</label>
										<div class="input-group">
											<span class="input-group-addon"><i class="fa fa-envelope"></i></span>
											<input type="email" name="lead_email" id="lead_email" class="form-control"
												placeholder="john.smith@acme.com (Example)" required>
										</div>
										<div class="btn-link" id="logout_txt" style="cursor: pointer; margin-top: 5px; text-align: end; color: red !important; font-size: 14px;" onclick="logout()">LogOut</div>
									</div>
								</div>
							</div>

							<div class="row">
								<!-- BD Website URL -->
								<div class="col-md-6">
									<div class="form-group">
										<label for="bd_website_url">BD Website URL *</label>
										<div class="input-group">
											<span class="input-group-addon"><i class="fa fa-globe"></i></span>
											<input type="text" name="bd_website_url" id="bd_website_url"  class="form-control"
												placeholder="https://yourwebsite.com" required>
										</div>
									</div>
								</div>

								<!-- Phone -->
								<div class="col-md-6">
									<div class="form-group">
										<label for="input-phone-number">Phone Number *</label>
										<div class="input-group">
											<span class="input-group-addon"><i class="fa fa-phone" aria-hidden="true"></i></span>
											<input type="tel" class="form-control" id="input-phone-number" name="input-phone-number"
												placeholder="+13025044225"
												pattern="^[\+0-9\s\-]{7,20}$"
												required>
										</div>
										<div style="font-size:13px;margin-top:6px;color:#555;">
											Include country code (e.g., +13025044225).
										</div>
										<span class="error_Msg" style="color:red;display:none;font-size:14px;">
											Please enter valid phone number
										</span>
									</div>
								</div>
							</div>

							<div class="row">
								<!-- Specifications -->
								<div class="col-md-6">
									<div class="form-group">
										<label for="specifications">Specification Link (if any)</label>
										<div class="input-group">
											<span class="input-group-addon"><i class="fa fa-link"></i></span>
											<input type="text" name="specifications" id="specifications" class="form-control"
												placeholder="Share link to Google Doc, wireframe, or mockup">
										</div>
										<div style="font-size:13px;margin-top:6px;color:#555;">
											Need good examples? Check a sample
											<a href="https://bdgrowthsuite.com/mockup" target="_blank"><strong>Mockup</strong></a> or
											<a href="https://docs.google.com/document/d/1nUPWQERf2O0PwxaUR2b6d3TSeRi_RTabxNBbB9Mrlkg/edit?tab=t.0" target="_blank"><strong>Technical Doc</strong></a>.
										</div>
									</div>
								</div>

								<!-- Upload File -->
								<div class="col-md-6">
									<div class="form-group">
										<label for="fileUpload">Upload File</label>
										<div class="input-group">
											<input type="text" id="fileNameDisplay" name="files_display" class="form-control"
												placeholder="No file chosen" readonly>
											<span class="input-group-btn">
												<label class="btn btn-default file-upload-label" for="fileUpload">
													Choose File
												</label>
												<input type="file" id="fileUpload" name="files" style="display:none;">
											</span>
										</div>
										<div style="font-size:13px;margin-top:6px;color:#555;">
											Upload any additional file like designs, docs, or feature lists (PDF/DOC).
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<!-- Quote Notes -->
								<div class="col-md-12">
									<div class="form-group">
										<label for="lead_notes">Requirements</label>
										<div class="input-group" style="width: 100%;">
											<textarea name="lead_notes" id="lead_notes" rows="4" class="form-control"
												placeholder="e.g., I need to integrate a custom API..." style="border-radius: 6px;" required></textarea>
										</div>
									</div>
								</div>

								<!-- Submit Button -->
								<div class="col-md-12">
									<div class="form-group" style="text-align:center;">
										<button type="submit" class="btn btn-success" id="signup-user-btn"
											style="background-color:#28a745;border:none;color:#fff;font-weight:bold;
							font-size:16px;padding:12px 24px;border-radius:6px;
							transition:background-color 0.3s ease;">
											Submit Estimate Request
										</button>
									</div>
								</div>
							</div>
						</form>
					</div>

				</div>
			</div>
		</div>
	</div>
</section>

<script>
	// ✅ Show selected file name dynamically
	document.getElementById('fileUpload').addEventListener('change', function() {
		const fileName = this.files.length ? this.files[0].name : 'No file chosen';
		document.getElementById('fileNameDisplay').value = fileName;
	});
</script>

<style>
	/* ✅ Input Consistency */
	.requirements_form .form-control {
		height: 44px;
		font-size: 15px;
		border-radius: 6px;
		border: 1px solid #ccc;
	}

	.requirements_form label {
		display: block;
		margin-bottom: 5px;
		font-weight: 600;
		font-size: 14px;
		color: #333;
	}

	.input-group-addon i {
		color: #666;
	}

	/* ✅ Mobile Optimization */
	@media (max-width: 768px) {
		.requirements_form .form-control {
			font-size: 16px;
			padding: 10px;
		}

		.input-group-addon i {
			font-size: 18px;
		}

		button#signup-user-btn {
			width: 100%;
			font-size: 17px;
			padding: 14px;
		}
	}


	#fileNameDisplay.form-control {
		border-top-right-radius: 0;
		border-bottom-right-radius: 0;
	}

	/* Remove rounding on the left side of the button label */
	.input-group-btn label.btn[for="fileUpload"] {
		border-top-left-radius: 0;
		border-bottom-left-radius: 0;

		/* Match the height of the .form-control (44px) for alignment */
		height: 44px;
	}
</style>