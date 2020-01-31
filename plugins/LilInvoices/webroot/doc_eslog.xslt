<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
	<xsl:decimal-format name="dec" decimal-separator="," grouping-separator="."/>
	<xsl:param name="header" />
	<xsl:param name="footer" />

	<xsl:template match="/IzdaniRacunEnostavni/Racun">
		<xsl:variable name="NASLOV_RACUNA">
			<xsl:call-template name="Parameter">
				<xsl:with-param name="name" select="'NASLOV_RACUNA'"/>
			</xsl:call-template>
		</xsl:variable>
		<xsl:variable name="TIP_DOKUMENTA">
			<xsl:call-template name="Parameter">
				<xsl:with-param name="name" select="'TIP_DOKUMENTA'"/>
			</xsl:call-template>
		</xsl:variable>
        <xsl:variable name="VRSTA_DOKUMENTA">
			<xsl:call-template name="Parameter">
				<xsl:with-param name="name" select="'VRSTA_DOKUMENTA'"/>
			</xsl:call-template>
		</xsl:variable>
		<xsl:variable name="STEVILKA_RACUNA" select="GlavaRacuna/StevilkaRacuna"/>
		<xsl:variable name="newline"><xsl:text>&#xD;&#xA;</xsl:text></xsl:variable>
		<html>
			<head>
				<title>
					<xsl:value-of select="concat('Dokument [ ', $NASLOV_RACUNA,' ] - št.: ', $STEVILKA_RACUNA)"/>
				</title>
				<style type="text/css">
                    td {
                        align: left;
                    }
                    #content {
                        margin-top: 0.5cm;
                    }

                    td#narocnik-wrapper{
                        height: 3cm;
                    }
                    table.narocnik .posta_kraj {
                        padding-top: 0.5cm;
                    }
                    table.narocnik .davcna {
                        padding-top: 0.5cm;
                    }

                    .bold { font-weight: bold; }
                </style>
			</head>
			<body>
                <xsl:variable name="narocnik" select="PodatkiPodjetja[NazivNaslovPodjetja/VrstaPartnerja='IV']"/>
				<xsl:variable name="narocnik_naziv_1" select="concat($narocnik/NazivNaslovPodjetja/NazivPartnerja/NazivPartnerja1, ' ', $narocnik/NazivNaslovPodjetja/NazivPartnerja/NazivPartnerja2)"/>
				<xsl:variable name="narocnik_naziv_2" select="concat($narocnik/NazivNaslovPodjetja/NazivPartnerja/NazivPartnerja3, ' ', $narocnik/NazivNaslovPodjetja/NazivPartnerja/NazivPartnerja4)"/>
				<xsl:variable name="narocnik_naslov_1" select="concat($narocnik/NazivNaslovPodjetja/Ulica/Ulica1, $narocnik/NazivNaslovPodjetja/Ulica/Ulica2)"/>
				<xsl:variable name="narocnik_naslov_2" select="concat($narocnik/NazivNaslovPodjetja/Ulica/Ulica3, $narocnik/NazivNaslovPodjetja/Ulica/Ulica4)"/>
				<xsl:variable name="narocnik_posta_kraj" select="concat($narocnik/NazivNaslovPodjetja/PostnaStevilka, ' ', $narocnik/NazivNaslovPodjetja/Kraj)"/>
				<xsl:variable name="narocnik_drzava" select="concat($narocnik/NazivNaslovPodjetja/KodaDrzave, ' - ', $narocnik/NazivNaslovPodjetja/NazivDrzave)"/>
				<xsl:variable name="narocnik_davcna" select="$narocnik/ReferencniPodatkiPodjetja[VrstaPodatkaPodjetja='VA']/PodatekPodjetja"/>
				<xsl:variable name="narocnik_maticna" select="$narocnik/ReferencniPodatkiPodjetja[VrstaPodatkaPodjetja='GN']/PodatekPodjetja"/>
				<xsl:variable name="narocnik_trr" select="$narocnik/FinancniPodatkiPodjetja[1]/BancniRacun/StevilkaBancnegaRacuna"/>

				<xsl:variable name="FAKTURIST">
					<xsl:call-template name="Parameter">
						<xsl:with-param name="name" select="'FAKTURIST'"/>
					</xsl:call-template>
				</xsl:variable>


				<xsl:variable name="GLAVA_TEKST" select="PoljubnoBesedilo/Besedilo[Tekst1 = 'GLAVA_TEKST']"/>
				<xsl:variable name="referencni_dokumenti" select="ReferencniDokumenti"/>

				<xsl:variable name="valuta" select="Valuta/KodaValute"/>
                <xsl:variable name="valuta_znak">
                  <xsl:call-template name="ValutaZnak">
                    <xsl:with-param name="valuta" select="$valuta"/>
                  </xsl:call-template>
                </xsl:variable>

				<xsl:variable name="kraj_izdaje" select="Lokacije[VrstaLokacije='91']/NazivLokacije"/>
				<xsl:variable name="datum_izdaje" select="DatumiRacuna[VrstaDatuma= '137']/DatumRacuna"/>
				<xsl:variable name="datum_storitve" select="DatumiRacuna[VrstaDatuma= '35']/DatumRacuna"/>
				<xsl:variable name="rok_placila" select="PlacilniPogoji[PodatkiORokih/VrstaPogoja = '3']/PlacilniRoki[VrstaDatumaPlacilnegaRoka = '13']/Datum"/>
				<xsl:variable name="sklic">
					<xsl:call-template name="format_sklic">
						<xsl:with-param name="sklicP" select="PovzetekZneskovRacuna[ZneskiRacuna/VrstaZneska = '9']/SklicZaPlacilo/StevilkaSklica"/>
					</xsl:call-template>
				</xsl:variable>
				<xsl:variable name="NACIN_PLACILA" select="GlavaRacuna/NacinPlacila" />
				<xsl:variable name="obrnjena_davcna_vrednost">
					<xsl:call-template name="Parameter">
						<xsl:with-param name="name" select="'OBRNJENA_DAVCNA_OBVEZNOST'"/>
					</xsl:call-template>
				</xsl:variable>

				<div id="print-area">
				    <xsl:if test="string-length($header) &gt; 0">
				        <div id="header1"><xsl:value-of select="$header" disable-output-escaping="yes" /></div>
				    </xsl:if>
				    <div id="content">
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
    					<td height="30"/>
    				</tr>
          <tr>
            <td id="narocnik-wrapper" valign="top">
              <table width="70%" class="narocnik" cellpadding="0" cellspacing="0">
                <tr>
                	<td colspan="2"><xsl:value-of select="$narocnik_naziv_1"/></td>
                </tr>
                <xsl:if test="string-length($narocnik_naziv_2) &gt; 0">
                	<tr>
                		<td colspan="2"><xsl:value-of select="$narocnik_naziv_2"/></td>
                	</tr>
                </xsl:if>
                <tr>
                	<td colspan="2"><xsl:value-of select="$narocnik_naslov_1"/></td>
                </tr>
			<xsl:if test="string-length($narocnik_naslov_2) &gt; 0">
				<tr>
                    <td>
                      <xsl:value-of select="$narocnik_naslov_2"/>
                    </td>
				</tr>
			</xsl:if>
				<tr>
					<td colspan="2" class="posta_kraj"><xsl:value-of select="$narocnik_posta_kraj"/></td>
				</tr>
				<tr>
					<td colspan="2"><xsl:value-of select="$narocnik_drzava"/></td>
				</tr>
				<tr>
					<td colspan="2" height="30"/>
				</tr>
            <xsl:if test="string-length($narocnik_davcna) &gt; 0">
							<tr class="davcna">
								<xsl:choose>
									<xsl:when test="string-length($narocnik_davcna) &gt; 8"><td width="40%">ID za DDV kupca:</td></xsl:when>
									<xsl:when test="string-length($narocnik_davcna) &lt; 9"><td width="40%">Davčna št. kupca:</td></xsl:when>
								</xsl:choose>
								<td><xsl:value-of select="$narocnik_davcna"/></td>
							</tr>
            </xsl:if>
                <xsl:if test="string-length($narocnik_maticna) &gt; 0">
								<tr>
									<td width="40%">Matična št. kupca:</td>
									<td><xsl:value-of select="$narocnik_maticna"/></td>
								</tr>
                </xsl:if>
							</table>
						</td>
					</tr>
                    <tr>
    					<td height="30"/>
    				</tr>
					<tr>
						<td>
							<table width="70%" class="basics" cellpadding="0" cellspacing="0">
								<tr>
									<td width="40%" class="big" style="border-bottom: 1px solid black;"><h1><xsl:value-of select="$TIP_DOKUMENTA"/> št.:</h1></td>
									<td width="60%" class="big" style="border-bottom: 1px solid black;"><h1><xsl:value-of select="$STEVILKA_RACUNA"/></h1></td>
								</tr>

								<tr>
									<td height="0.3cm"/>
								</tr>

							<xsl:if test="$GLAVA_TEKST">
								<xsl:for-each select="$GLAVA_TEKST">
									<tr>
										<td colspan="2">
											<xsl:call-template name="ConcatBesedilo">
												<xsl:with-param name="besedilo" select="."/>
											</xsl:call-template>
										</td>
									</tr>
								</xsl:for-each>
							</xsl:if>

							<xsl:if test="$referencni_dokumenti">
								<tr>
									<td height="5px"/>
								</tr>
								<tr>
									<td valign="top">
										<b>Referenčni dokumenti:</b>
									</td>
									<td valign="top">
										<xsl:for-each select="$referencni_dokumenti">
											<xsl:if test="@VrstaDokumenta='ON'">Naročilnica, </xsl:if>
											<xsl:if test="@VrstaDokumenta='CT'">Pogodba, </xsl:if>
											<xsl:if test="@VrstaDokumenta='AAK'">Dobavnica, </xsl:if>
											<xsl:value-of select="StevilkaDokumenta"/> z dne
											<xsl:call-template name="format_date">
												<xsl:with-param name="date" select="DatumDokumenta"/>
											</xsl:call-template>
											<br/>
										</xsl:for-each>
									</td>
								</tr>
							</xsl:if>

								<tr>
									<td width="40%"><b>Valuta:</b></td>
									<td width="60%"><xsl:value-of select="$valuta"/></td>
								</tr>
								<tr>
									<td width="40%"><b>Kraj izdaje:</b></td>
									<td width="60%"><xsl:value-of select="$kraj_izdaje"/></td>
								</tr>
								<tr>
									<td width="40%"><b>Datum izdaje:</b></td>
									<td width="60%">
										<xsl:call-template name="format_date">
											<xsl:with-param name="date" select="$datum_izdaje"/>
										</xsl:call-template>
									</td>
								</tr>
								<tr>
									<td width="40%"><b>Datum zapadlosti:</b></td>
									<td width="60%">
										<xsl:call-template name="format_date">
											<xsl:with-param name="date" select="$rok_placila"/>
										</xsl:call-template>
									</td>
								</tr>
								<xsl:if test="$datum_storitve != ''">
								<tr>
									<td width="40%"><b>Datum storitve:</b></td>
									<td width="60%">
										<xsl:call-template name="format_date">
											<xsl:with-param name="date" select="$datum_storitve"/>
										</xsl:call-template>
									</td>
								</tr>
								</xsl:if>
								<tr>
									<td width="40%"><b>Sklic:</b></td>
									<td width="60%">
										<xsl:value-of select="$sklic"/>
									</td>
								</tr>
								<tr>
									<td width="40%"><b>Koda namena:</b></td>
									<td width="60%">
										<xsl:value-of  select="GlavaRacuna/KodaNamena"/>
									</td>
								</tr>
								<tr>
									<td width="40%" valign="top"><b>Način plačila:</b></td>
									<td width="60%">
										<xsl:if test="$NACIN_PLACILA = '0'">Račun je potrebno plačati</xsl:if>
										<xsl:if test="$NACIN_PLACILA = '1'">Račun bo plačan preko direktne obremenitve in ga ni potrebno plačati</xsl:if>
										<xsl:if test="$NACIN_PLACILA = '2'">Račun je bil že plačan</xsl:if>
										<xsl:if test="$NACIN_PLACILA = '3'">Drugo/ni možnosti plačila</xsl:if>
									</td>
								</tr>
								<tr>
									<td width="40%"><b>Obrn. davčna vrednost:</b></td>
									<td width="60%">
										<xsl:choose>
											<xsl:when test="$obrnjena_davcna_vrednost='true'">da</xsl:when>
											<xsl:otherwise>ne</xsl:otherwise>
										</xsl:choose>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td height="20px"/>
					</tr>
					<tr>
						<td>
							<table cellpadding="2" cellspacing="0" style="border: 1px solid black;">
								<tr>
									<td valign="top" style="border-right: 1px solid black; border-bottom: 1px solid black;" width="4%"><b>Z.št.</b></td>
									<td valign="top" style="border-right: 1px solid black; border-bottom: 1px solid black;" width="20%"><b>Artikel/storitev</b></td>
									<td valign="top" style="border-right: 1px solid black; border-bottom: 1px solid black;" width="8%" align="right"><b>Kol.</b></td>
									<td valign="top" style="border-right: 1px solid black; border-bottom: 1px solid black;" width="8%" align="center"><b>E.M.</b></td>
									<td valign="top" style="border-right: 1px solid black; border-bottom: 1px solid black;" width="14%" align="right"><b>Cena brez DDV</b></td>
									<td valign="top" style="border-right: 1px solid black; border-bottom: 1px solid black;" width="9%" align="center"><b>Popust<br />[%]</b></td>
									<td valign="top" style="border-right: 1px solid black; border-bottom: 1px solid black;" width="9%" align="center"><b>DDV<br />[%]</b></td>
									<td valign="top" style="border-right: 1px solid black; border-bottom: 1px solid black;" width="14%" align="right"><b>DDV</b></td>
									<td valign="top" style=" border-bottom: 1px solid black;" width="14%" align="right"><b>Cena z DDV</b></td>
								</tr>
								<xsl:for-each select="PostavkeRacuna">
									<xsl:variable name="st_vrstice" select="Postavka/StevilkaVrstice"/>
									<xsl:variable name="opis">
										<xsl:for-each select="OpisiArtiklov[KodaOpisaArtikla]/OpisArtikla">
											<xsl:value-of select="concat(OpisArtikla1, OpisArtikla2)"/>
										</xsl:for-each>
									</xsl:variable>
									<xsl:variable name="kolicina" select="format-number(KolicinaArtikla/Kolicina, '###.###.##0,##', 'dec')"/>
									<xsl:variable name="enota_mere" select="KolicinaArtikla/EnotaMere"/>
									<xsl:variable name="cena" select="format-number(CenaPostavke/Cena, '###.###.##0,00', 'dec')"/>
									<xsl:variable name="koncna_vrednost" select="format-number(ZneskiPostavke[VrstaZneskaPostavke='38']/ZnesekPostavke, '###.###.##0,00', 'dec')"/>
									<xsl:variable name="popust_znesek">
										<xsl:for-each select="OdstotkiPostavk[VrstaZneskaOdstotka='204']">
											<xsl:if test="position()=1">
												<xsl:value-of select="format-number(ZnesekOdstotka, '###.###.##0,00', 'dec')"/>
											</xsl:if>
										</xsl:for-each>
									</xsl:variable>
									<xsl:variable name="stopnja_ddv">
										<xsl:choose>
											<xsl:when test="DavkiPostavke/DavkiNaPostavki/OdstotekDavkaPostavke">
												<xsl:value-of select="format-number(DavkiPostavke/DavkiNaPostavki/OdstotekDavkaPostavke, '###.###.##0,##', 'dec')"/>
											</xsl:when>
											<xsl:otherwise>ne obdav.</xsl:otherwise>
										</xsl:choose>
									</xsl:variable>
									<xsl:variable name="osnova_ddv">
										<xsl:if test="DavkiPostavke[DavkiNaPostavki]/ZneskiDavkovPostavke[VrstaZneskaDavkaPostavke='125']/Znesek">
											<xsl:value-of select="format-number(DavkiPostavke/ZneskiDavkovPostavke[VrstaZneskaDavkaPostavke='125']/Znesek, '###.###.##0,00', 'dec')"/>
										</xsl:if>
									</xsl:variable>
									<xsl:variable name="ddv">
										<xsl:if test="DavkiPostavke[DavkiNaPostavki]/ZneskiDavkovPostavke[VrstaZneskaDavkaPostavke='124']/Znesek">
											<xsl:value-of select="format-number(DavkiPostavke/ZneskiDavkovPostavke[VrstaZneskaDavkaPostavke='124']/Znesek, '###.###.##0,00', 'dec')"/>
										</xsl:if>
									</xsl:variable>
									<xsl:variable name="popust_procent">
										<xsl:if test="OdstotkiPostavk/OdstotekPostavke">
											<xsl:value-of select="concat(format-number(OdstotkiPostavk/OdstotekPostavke, '###.###.##0,##', 'dec'), '%')"/>
										</xsl:if>
									</xsl:variable>
									<xsl:variable name="postavka_navadna" select="OpisiArtiklov[not(KodaOpisaArtikla)]/OpisArtikla[OpisArtikla1 = 'OZNAKA_POSTAVKE']/OpisArtikla2='navadna'"/>
									<xsl:variable name="postavka_opisna" select="OpisiArtiklov[not(KodaOpisaArtikla)]/OpisArtikla[OpisArtikla1 = 'OZNAKA_POSTAVKE']/OpisArtikla2='opisna'"/>
									<xsl:variable name="postavka_obresti" select="OpisiArtiklov[not(KodaOpisaArtikla)]/OpisArtikla[OpisArtikla1 = 'OZNAKA_POSTAVKE']/OpisArtikla2='obresti'"/>
									<xsl:variable name="postavka_soudelezba" select="OpisiArtiklov[not(KodaOpisaArtikla)]/OpisArtikla[OpisArtikla1 = 'OZNAKA_POSTAVKE']/OpisArtikla2='soudelezba'"/>
									<xsl:if test="$postavka_opisna">
										<tr>
											<td style="border-bottom: 1px solid gray;">&#160;</td>
											<td colspan="10" style="border-bottom: 1px solid gray;"><b><xsl:value-of select="$opis"/></b></td>
										</tr>
									</xsl:if>
									<xsl:if test="$postavka_navadna or $postavka_soudelezba or $postavka_obresti">
										<tr>
											<td valign="top"><xsl:value-of select="$st_vrstice"/></td>
											<td valign="top"><xsl:value-of select="$opis" disable-output-escaping="yes"/></td>
											<td valign="top" align="right"><xsl:value-of select="$kolicina"/></td>
											<td valign="top" align="center"><xsl:value-of select="$enota_mere"/></td>
											<td valign="top" align="right"><xsl:value-of select="$cena"/></td>
											<td valign="top" align="center"><xsl:value-of select="$popust_procent"/></td>
											<td valign="top" align="center"><xsl:value-of select="$stopnja_ddv"/></td>
											<td valign="top" align="right"><xsl:value-of select="$ddv"/></td>
											<td valign="top" align="right"><xsl:value-of select="$koncna_vrednost"/></td>
										</tr>
									</xsl:if>

								</xsl:for-each>
							</table>
						</td>
					</tr>
					<tr>
						<td height="20px"/>
					</tr>
					<tr>
						<td>
							<table width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td width="55%">
										<table width="100%" cellpadding="0" cellspacing="0" border="0">
											<tr>
												<td width="22%" align="right" style="border-bottom: 1px solid black;"><b>Stopnja DDV</b></td>
												<td width="22%" align="right" style="border-bottom: 1px solid black;"><b>Osnova DDV</b></td>
												<td width="22%" align="right" style="border-bottom: 1px solid black;"><b>Vrednost DDV</b></td>
												<td width="12%"/>
											</tr>
											<xsl:for-each select="PovzetekDavkovRacuna">
												<xsl:variable name="pddv_stopnja" select="format-number(DavkiRacuna/OdstotekDavka, '###.###.##0,##', 'dec')"/>
												<xsl:variable name="pddv_osnova" select="format-number(ZneskiDavkov[VrstaZneskaDavka='125']/ZnesekDavka, '###.###.##0,00', 'dec')"/>
												<xsl:variable name="pddv" select="format-number(ZneskiDavkov[VrstaZneskaDavka='124']/ZnesekDavka, '###.###.##0,00', 'dec')"/>
												<xsl:choose>
													<xsl:when test="DavkiRacuna/VrstaDavka">
														<tr>
															<td align="right"><xsl:value-of select="$pddv_stopnja"/> %</td>
															<td align="right"><xsl:value-of select="$pddv_osnova"/>  €</td>
															<td align="right"><xsl:value-of select="$pddv"/> €</td>
														</tr>
													</xsl:when>
													<xsl:otherwise>
														<tr>
															<td align="right">neobdavčeno</td>
															<td align="right"><xsl:value-of select="$pddv_osnova"/></td>
														</tr>
													</xsl:otherwise>
												</xsl:choose>
											</xsl:for-each>
											<tr>
												<td colspan="5" height="15px"/>
											</tr>
											<xsl:for-each select="PoljubnoBesedilo/Besedilo[Tekst1='DAVCNI_TEKST']">
												<tr>
													<td colspan="5"><xsl:value-of select="concat(Tekst2, Tekst3, Tekst4, Tekst5)"/></td>
												</tr>
											</xsl:for-each>
											<tr>
												<td colspan="5" height="1cm"/>
											</tr>
                                            <tr>
												<td colspan="5"><br />
											<xsl:for-each select="PoljubnoBesedilo/Besedilo[Tekst1='DODATNI_TEKST']">

                                                        <xsl:value-of select="Tekst2" disable-output-escaping="yes"/>
                                                        <xsl:value-of select="Tekst3" disable-output-escaping="yes"/>
                                                        <xsl:value-of select="Tekst4" disable-output-escaping="yes"/>
                                                        <xsl:value-of select="Tekst5" disable-output-escaping="yes"/>

											</xsl:for-each>
                                                </td>
											</tr>
											<tr>
												<td colspan="5" height="15px"/>
											</tr>
											<tr>
												<td colspan="5"><b>Fakturist: </b><xsl:value-of select="$FAKTURIST"/></td>
											</tr>
										</table>
									</td>
									<td width="5%"/>
									<td width="40%" valign="top">
										<xsl:variable name="zneski_vrednost" select="PovzetekZneskovRacuna/ZneskiRacuna[VrstaZneska='79']/ZnesekRacuna"/>
										<xsl:variable name="zneski_popust" select="PovzetekZneskovRacuna/ZneskiRacuna[VrstaZneska='53']/ZnesekRacuna"/>
										<xsl:variable name="zneski_osnovaDDV" select="PovzetekZneskovRacuna/ZneskiRacuna[VrstaZneska='125']/ZnesekRacuna"/>
										<xsl:variable name="zneski_vrednostDDV" select="PovzetekZneskovRacuna/ZneskiRacuna[VrstaZneska='176']/ZnesekRacuna"/>
										<xsl:variable name="neobdavceno" select="PovzetekDavkovRacuna[not(DavkiRacuna/VrstaDavka)]/ZneskiDavkov[VrstaZneskaDavka='125']/ZnesekDavka"/>
										<xsl:variable name="zneski_vrednostZDDV" select="PovzetekZneskovRacuna/ZneskiRacuna[VrstaZneska='86']/ZnesekRacuna"/>
										<xsl:variable name="znesek_zamudneObr" select="PovzetekZneskovRacuna/ZneskiRacuna[VrstaZneska='131']/ZnesekRacuna"/>
										<xsl:variable name="znesek_dobropis" select="PovzetekZneskovRacuna/ZneskiRacuna[VrstaZneska='113']/ZnesekRacuna"/>
										<xsl:variable name="znesek_izravnava" select="PovzetekZneskovRacuna/ZneskiRacuna[VrstaZneska='165']/ZnesekRacuna"/>
										<xsl:variable name="znesek_zaPlacilo" select="PovzetekZneskovRacuna/ZneskiRacuna[VrstaZneska='9']/ZnesekRacuna"/>

										<table width="100%" cellpadding="3" cellspacing="0" border="0">
											<xsl:if test="string-length($zneski_vrednost) &gt; 0">
												<tr>
													<td width="60%"><b>Vrednost postavk:</b></td>
													<td width="40%" align="right"><xsl:value-of select="format-number($zneski_vrednost, '###.###.##0,00', 'dec')"/> €</td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length($zneski_popust) &gt; 0">
												<tr>
													<td><b>Vsota popustov:</b></td>
													<td align="right"><xsl:value-of select="format-number($zneski_popust, '###.###.##0,00', 'dec')"/> €</td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length($zneski_osnovaDDV) &gt; 0">
												<tr>
													<td><b>Osnova za DDV:</b></td>
													<td align="right"><xsl:value-of select="format-number($zneski_osnovaDDV, '###.###.##0,00', 'dec')"/> €</td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length($neobdavceno) &gt; 0">
												<tr>
													<td><b>Neobdavčeno:</b></td>
													<td align="right"><xsl:value-of select="format-number($neobdavceno, '###.###.##0,00', 'dec')"/> €</td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length($zneski_vrednostDDV) &gt; 0">
												<tr>
													<td><b>Vsota zneskov DDV:</b></td>
													<td align="right"><xsl:value-of select="format-number($zneski_vrednostDDV, '###.###.##0,00', 'dec')"/> €</td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length($zneski_vrednostZDDV) &gt; 0">
												<tr>
													<td><b>Vsota s popusti in davki:</b></td>
													<td align="right"><xsl:value-of select="format-number($zneski_vrednostZDDV, '###.###.##0,00', 'dec')"/> €</td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length($znesek_zamudneObr) &gt; 0">
												<tr>
													<td><b>Zamudne obresti:</b></td>
													<td align="right"><xsl:value-of select="format-number($znesek_zamudneObr, '###.###.##0,00', 'dec')"/> €</td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length($znesek_dobropis) &gt; 0">
												<tr>
													<td><b>Predplačilo:</b></td>
													<td align="right"><xsl:value-of select="format-number($znesek_dobropis, '###.###.##0,00', 'dec')"/> €</td>
												</tr>
											</xsl:if>
											<xsl:if test="string-length($znesek_izravnava) &gt; 0">
												<tr>
													<td><b>Izravnava:</b></td>
													<td align="right"><xsl:value-of select="format-number($znesek_izravnava, '###.###.##0,00', 'dec')"/> €</td>
												</tr>
											</xsl:if>
											<tr>
												<td style="border-top: 1px solid black; border-bottom: 1px solid black;"><b>Za plačilo:</b></td>
												<td style="border-top: 1px solid black; border-bottom: 1px solid black;" align="right">
                          <xsl:value-of select="format-number($znesek_zaPlacilo, '###.###.##0,00', 'dec')"/>
                          <span style="padding-left: 2px;"><xsl:value-of select="$valuta_znak"/></span>
                        </td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td height="1cm"/>
					</tr>
					<tr>
						<td align="center">
							<xsl:for-each select="PoljubnoBesedilo/Besedilo[Tekst1='NOGA_TEKST']">
								<xsl:value-of select="concat(Tekst2, Tekst3, Tekst4, Tekst5)" disable-output-escaping="yes" />
							</xsl:for-each>
						</td>
					</tr>
				</table>

                    </div>
                    <xsl:if test="string-length($footer) &gt; 0">
                        <div id="footer1"><xsl:value-of select="$footer" disable-output-escaping="yes" /></div>
                    </xsl:if>
                </div>
			</body>
		</html>
	</xsl:template>

	<xsl:template name="Parameter">
		<xsl:param name="name"/>
		<xsl:for-each select="PoljubnoBesedilo/Besedilo[Tekst1 = $name]">
			<xsl:value-of select="concat(Tekst2, Tekst3, Tekst4, Tekst5)"/>
		</xsl:for-each>
	</xsl:template>

  <xsl:template name="ValutaZnak">
    <xsl:param name="valuta"/>
    <xsl:choose>
      <xsl:when test="$valuta = 'EUR'">€</xsl:when>
      <xsl:when test="$valuta = 'USD'">$</xsl:when>
      <xsl:when test="$valuta = 'GBP'">£</xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$valuta"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

	<xsl:template name="PostavkaParameter">
		<xsl:param name="name"/>
		<xsl:for-each select="OpisiArtiklov[not(KodaOpisaArtikla)]/OpisArtikla[OpisArtikla1 = $name]">
			<xsl:value-of select="OpisArtikla2"/>
		</xsl:for-each>
	</xsl:template>

	<xsl:template name="format_date">
		<xsl:param name="date"/>
		<xsl:value-of select="concat(substring($date, 9, 2), '.', substring($date, 6, 2), '.', substring($date, 1, 4))"/>
	</xsl:template>

	<xsl:template name="format_sklic">
		<xsl:param name="sklicP"/>
		<xsl:value-of select="concat(substring($sklicP, 1, 4), ' - ', substring($sklicP, 5))"/>
	</xsl:template>

	<xsl:template name="ConcatBesedilo">
		<xsl:param name="besedilo"/>
		<xsl:value-of select="concat($besedilo/Tekst2, $besedilo/Tekst3, $besedilo/Tekst4, $besedilo/Tekst5)"/>
	</xsl:template>

	<xsl:template match="text()"/>
</xsl:stylesheet>
