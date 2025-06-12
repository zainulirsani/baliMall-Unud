<?php include('metadata.php') ?>
<?php include('file-nav.php') ?>
<?php include('header-home.php') ?>
<div class="red-box"></div>
<main class="pdl dbu">
	<!-- <section class="brc">
		<div class="container">
			<span class="mobile"><a href="">Balimall</a></span><span class="mobile"><i class="fas fa-chevron-right"></i></span>
			<span class="current mobile"><a href="">Dashboard</a></span>
		</div>
	</section> -->
	<section>
		<div class="container">
			<div class="box">
				<div class="row">
					<div class="dc4 tc4 mc4">
						<div class="tab">
                            <button class="tablinks" onclick="myTabs(event, 'Toko')" id="defaultOpen">
                                <i class="fas fa-check green"></i>
                                Data Toko
                            </button>
                            <button class="tablinks" onclick="myTabs(event, 'PemilikUsaha')">
                                Data Pemilik Usaha
                            </button>
                            <button class="tablinks" onclick="myTabs(event, 'Keuangan')">
                                Data Keuangan
                            </button>
                            <button class="tablinks" onclick="myTabs(event, 'Perpajakan')">
                                Data Perpajakan
                            </button>
                            <button class="tablinks" onclick="myTabs(event, 'Kerjasama')">
                                Kerjasama
                            </button>
                            <button class="tablinks" onclick="myTabs(event, 'finish')">
                                finish
                            </button>
                        </div>
					</div>
					<div class="dc8 tc8 mc8">
						<div id="Toko" class="tabcontent">
                            <form action="" method="post">
                                <div class="row">
                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">Nama Toko <span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                    </div>
                                    <div class="dc2 mc12 btn-u">
                                        <div class="file-input">
                                            <p>Logo Toko <span>*</span></p>
                                            <div class="imageUp">
                                                <img src="../assets/img/bali-bangkit.png" class="pic" alt="">
                                            </div>
                                            <div class='file file--upload'>
                                                <label for='input-file'>
                                                    Upload
                                                </label>
                                                <input id='input-file' type='file' />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dc2 mc12 btn-u">
                                        <div class="file-input">
                                            <p>Foto Dashboard Toko <span>*</span></p>
                                            <div class="imageUp">
                                                <img src="../assets/img/bali-bangkit.png" class="pic" alt="">
                                            </div>
                                            <div class='file file--upload'>
                                                <label for='input-file'>
                                                    Upload
                                                </label>
                                                <input id='input-file' type='file' />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">Nama Usaha <span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                            <div class="text-small">(Sesuai dengan nama yang tercantum di Ijin Usaha)</div>
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Alamat Tempat Usaha <span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Provinsi <span>*</span></label>
                                            <select class="form-control">
                                                <option>--Pilih--</option>
                                            </select>
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Kabupaten <span>*</span></label>
                                            <select class="form-control">
                                                <option>--Pilih--</option>
                                            </select>
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Kode Pos <span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">Jenis Usaha <span>*</span></label>
                                            <select class="form-control">
                                                <option>--Pilih--</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">Nama Toko <span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                    </div>
                                    <div class="dc2 mc12 btn-u">
                                        <div class="file-input">
                                            <p class="label">NPWP <span>*</span></p>
                                            <div class='file file--upload'>
                                                <label for='input-file'>
                                                    Upload
                                                </label>
                                                <input id='input-file' type='file' />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">Surat Ijin Usaha <span>*</span></label>
                                            <div class="imagesUp">
                                                <div class="img">
                                                    <img src="../assets/img/bali-bangkit.png" alt="">
                                                </div>
                                                <div class="pic">
                                                <i class="fas fa-plus"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Dokumen Tambahan (PIRT/BPOM/HAKI/lainnya) <span>*</span></label>
                                            <div class="imagesUp">
                                                <div class="img">
                                                    <img src="../assets/img/bali-bangkit.png" alt="">
                                                </div>
                                                <div class="pic">
                                                <i class="fas fa-plus"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="dc12">
                                        <div class="input form-group">
                                            <label for="">Kurir Pengiriman <span>*</span></label>
                                            <div class="checklist">
                                                <input type="checkbox" id="kantor" name="kantor" value="Friday" checked>
                                                <label for="kantor" class="checkL">Kantor Balimall.id  (Catatan : biaya pengiriman dari lokasi merchant ke kantor
                                                    balimall.id menjadi tanggungan merchant)</label>
                                                <input type="checkbox" id="kurir-1" name="kurir-1" value="Friday">
                                                <label for="kurir-1" class="checkL">Kurir</label>
                                                <input type="checkbox" id="kurir-2" name="kurir-2" value="Friday">
                                                <label for="kurir-2" class="checkL">Kurir</label>
                                                <input type="checkbox" id="kurir-3" name="kurir-3" value="Friday">
                                                <label for="kurir-3" class="checkL">Kurir</label>
                                                <input type="checkbox" id="kurir-4" name="kurir-4" value="Friday">
                                                <label for="kurir-4" class="checkL">Kurir</label>
                                                <input type="checkbox" id="kurir-5" name="kurir-5" value="Friday">
                                                <label for="kurir-5" class="checkL">Kurir</label>
                                            </div>
                                        </div>
                                    </div>

                                    
                                </div>
                                <div class="input">
                                    <button class="sBtn red">Simpan/Lanjut</button>
                                </div>
                            </form>
                        </div>

                        <div id="PemilikUsaha" class="tabcontent">
                            <form action="">
                                <div class="row">
                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">Nama Lengkap Pemilik Usaha <span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                    </div>
                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">NIK <span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                    </div>
                                    <div class="dc2 mc12 btn-u">
                                        <div class="file-input">
                                            <p class="label">KTP <span>*</span></p>
                                            <div class='file file--upload'>
                                                <label for='input-file'>
                                                    Upload
                                                </label>
                                                <input id='input-file' type='file' />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">Tanggal Lahir <span>*</span></label>
                                            <input type="date" name="" id="" class="form-control">
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Jenis Kelamin <span>*</span></label>
                                            <select class="form-control">
                                                <option>--Pilih--</option>
                                            </select>
                                        </div>
                                        <div class="input form-group">
                                            <label for="">No HP<span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="input">
                                    <button class="sBtn red">Simpan/Lanjut</button>
                                </div>
                            </form>
                        </div>

                        <div id="Keuangan" class="tabcontent">
                            <form action="">
                                <div class="row">
                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">Modal Usaha<span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                            <div class="text-small">(Nilai modal usaha sesuai dengan yang tercantum pada ijin usaha)</div>
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Jumlah Tenaga Kerja<span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Nama Rekening Perusahaan/Pemilik Usaha<span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Bank<span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                        <div class="input form-group">
                                            <label for="">No Rekening<span>*</span></label>
                                            <input type="text" name="" id="" class="form-control">
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Foto/Scan Halaman Depan Buku Tabungan
                                                Yang Menunjukan Rekening <span>*</span></label>
                                            <div class="imagesUp">
                                                <div class="img">
                                                    <img src="../assets/img/bali-bangkit.png" alt="">
                                                </div>
                                                <div class="pic">
                                                    <i class="fas fa-plus"></i>
                                                </div>
                                            </div>
                                            <div class="text-small">Format jpeg/pdf.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="input">
                                    <button class="sBtn red">Simpan/Lanjut</button>
                                </div>
                            </form>
                        </div>
                        <div id="Perpajakan" class="tabcontent">
                            <form action="">
                                <div class="row">
                                    <div class="dc8 mc12">
                                        <div class="input form-group">
                                            <label for="">Status Pajak <span>*</span></label>
                                            <select class="form-control">
                                                <option>--Pilih--</option>
                                            </select>
                                        </div>
                                        <div class="input form-group">
                                            <label for="">Surat Pengukuhan Pengusaha Kena Pajak (SPPKP) <span>*</span></label>
                                            <div class="imagesUp">
                                                <div class="img">
                                                    <img src="../assets/img/bali-bangkit.png" alt="">
                                                </div>
                                                <div class="pic">
                                                    <i class="fas fa-plus"></i>
                                                </div>
                                            </div>
                                            <div class="text-small">Format jpeg/pdf.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="input">
                                    <button class="sBtn red">Simpan/Lanjut</button>
                                </div>
                            </form>
                        </div>
                        <div id="Kerjasama" class="tabcontent">
                            <div class="row">
                                <div class="dc12">
                                    <div class="tabs">
                                        <button class="linkTab" onclick="myNav(event, 'Pernyataan')" id="Opened">
                                            <i class="fas fa-check green"></i>
                                            Surat Pernyataan
                                        </button>
                                        <button class="linkTab" onclick="myNav(event, 'Perjanjian')">
                                            Perjanjian Kerjasama
                                        </button>
                                    </div>
                                    
                                    <div id="Pernyataan" class="contentTab">
                                        <h3 class="ct">Surat Pernyataan</h3>
                                        <p>Isi text disini</p>
                                    </div>
                                    
                                    <div id="Perjanjian" class="contentTab">
                                        <h3 class="ct">Perjanjian Kerjasama</h3>
                                        <p>Isi text disini</p> 
                                    </div>
                                </div>
    
                                <div class="dc12">
                                    <div class="input form-group">
                                        <div class="checklist2">
                                            <input type="checkbox" id="kantor" name="kantor" value="Friday">
                                            <label for="kantor" class="checkL"></label>
                                            <div class="textTerm">
                                                <p><b>Dengan ini saya menyatakan telah membaca, memahami dan menyetujui surat pernyataan diatas</b></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="input">
                                <button class="sBtn red">Simpan/Lanjut</button>
                            </div>
                        </div>
                        <div id="finish" class="tabcontent">
                            <div class="icon-f ct">
                                <i class="far fa-check-circle"></i>
                            </div>
                            <h5 class="ct finH">
                                TERIMAKASIH TELAH MENGISI DATA USAHA DENGAN LENGKAP MOHON MENUNGGU PROSES VERIFIKASI DATA
                            </h5>
                            <p class="ct finP">
                                Pemberitahuan hasil verifikasi akan disampaikan melalui email atau melalui dashboard Toko
                            </p>
                        </div>
					</div>
				</div>
			</div>	
		</div>
	</section>
</main>

<script>
    function myTabs(evt, tabsName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabsName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    
    // Get the element with id="defaultOpen" and click on it
    document.getElementById("defaultOpen").click();
</script>

<!-- Script Kerjasama -->
<script>
    function myNav(evt, navName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("contentTab");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("linkTab");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(navName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    document.getElementById("Opened").click();
</script>
<!-- Script Kerjasama -->

<script>
    const file = document.querySelector('#file');
    file.addEventListener('change', (e) => {
    // Get the selected file
    const [file] = e.target.files;
    // Get the file name and size
    const { name: fileName, size } = file;
    // Convert size in bytes to kilo bytes
    const fileSize = (size / 1000).toFixed(2);
    // Set the text content
    const fileNameAndSize = `${fileName} - ${fileSize}KB`;
    document.querySelector('.file-name').textContent = fileNameAndSize;
    });
</script>

<?php include('footer.php') ?>
	
