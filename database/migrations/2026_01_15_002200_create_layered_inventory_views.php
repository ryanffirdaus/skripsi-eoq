<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create layered database views for inventory metrics
     * Layer 1: Transaction statistics and aggregates
     * Layer 2: EOQ/ROP/Safety Stock calculations  
     * Layer 3: Stock status and alerts
     */
    public function up(): void
    {
        // LAYER 1: Transaction Statistics View
        // Aggregates demand, lead time, and cost data from transactions
        DB::statement("
            CREATE OR REPLACE VIEW v_bahan_baku_transaction_stats AS
            SELECT 
                bb.bahan_baku_id,
                bb.nama_bahan,
                bb.stok_bahan,
                bb.harga_bahan,
                bb.satuan_bahan,
                bb.lokasi_bahan,
                bb.biaya_pemesanan_per_order,
                bb.biaya_penyimpanan_persen,
                
                -- Holding cost (calculated from % and price)
                (bb.harga_bahan * bb.biaya_penyimpanan_persen / 100) as biaya_penyimpanan,
                
                -- Average daily demand (from last 90 days)
                COALESCE(
                    (SELECT AVG(daily_demand) 
                     FROM (
                         SELECT DATE(p.created_at) as date_val, 
                                SUM(pd.qty_diminta) as daily_demand
                         FROM pengadaan p
                         INNER JOIN pengadaan_detail pd ON p.pengadaan_id = pd.pengadaan_id
                         WHERE pd.jenis_barang = 'bahan_baku'
                           AND pd.barang_id = bb.bahan_baku_id
                           AND p.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                           AND p.deleted_at IS NULL
                         GROUP BY date_val
                     ) daily_demands),
                    0
                ) as permintaan_harian_rata2,
                
                -- Standard deviation of daily demand
                COALESCE(
                    (SELECT STDDEV(daily_demand)
                     FROM (
                         SELECT DATE(p.created_at) as date_val, 
                                SUM(pd.qty_diminta) as daily_demand
                         FROM pengadaan p
                         INNER JOIN pengadaan_detail pd ON p.pengadaan_id = pd.pengadaan_id
                         WHERE pd.jenis_barang = 'bahan_baku'
                           AND pd.barang_id = bb.bahan_baku_id
                           AND p.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                           AND p.deleted_at IS NULL
                         GROUP BY date_val
                     ) daily_demands),
                    10
                ) as permintaan_stddev,
                
                -- Annual demand (daily avg * 365)
                COALESCE(
                    (SELECT AVG(daily_demand) * 365
                     FROM (
                         SELECT DATE(p.created_at) as date_val, 
                                SUM(pd.qty_diminta) as daily_demand
                         FROM pengadaan p
                         INNER JOIN pengadaan_detail pd ON p.pengadaan_id = pd.pengadaan_id
                         WHERE pd.jenis_barang = 'bahan_baku'
                           AND pd.barang_id = bb.bahan_baku_id
                           AND p.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                           AND p.deleted_at IS NULL
                         GROUP BY date_val
                     ) daily_demands),
                    0
                ) as permintaan_tahunan,
                
                -- Average lead time (days from pembelian to penerimaan)
                COALESCE(
                    (SELECT AVG(DATEDIFF(prb.created_at, pb.tanggal_pembelian))
                     FROM penerimaan_bahan_baku prb
                     INNER JOIN pembelian_detail pbd ON prb.pembelian_detail_id = pbd.pembelian_detail_id
                     INNER JOIN pembelian pb ON pbd.pembelian_id = pb.pembelian_id
                     INNER JOIN pengadaan_detail pd ON pbd.pengadaan_detail_id = pd.pengadaan_detail_id
                     WHERE pd.jenis_barang = 'bahan_baku'
                       AND pd.barang_id = bb.bahan_baku_id
                       AND prb.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                       AND prb.deleted_at IS NULL),
                    7
                ) as waktu_tunggu_rata2,
                
                bb.created_at,
                bb.updated_at
                
            FROM bahan_baku bb
            WHERE bb.deleted_at IS NULL
        ");
        
        // LAYER 2: Inventory Metrics View
        // Calculates EOQ, ROP, and Safety Stock from Layer 1 data
        DB::statement("
            CREATE OR REPLACE VIEW v_bahan_baku_inventory_metrics AS
            SELECT 
                s.*,
                
                -- EOQ Calculation: SQRT((2 * D * S) / H)
                CASE 
                    WHEN s.harga_bahan > 0 
                     AND s.biaya_penyimpanan > 0 
                     AND s.permintaan_tahunan > 0 THEN
                        SQRT(
                            (2 * s.permintaan_tahunan * s.biaya_pemesanan_per_order) / 
                            s.biaya_penyimpanan
                        )
                    ELSE 0
                END as eoq_bahan,
                
                -- Safety Stock: Z * σ * √L
                -- Z = 1.65 for 95% service level
                CASE 
                    WHEN s.permintaan_stddev > 0 AND s.waktu_tunggu_rata2 > 0 THEN
                        1.65 * s.permintaan_stddev * SQRT(s.waktu_tunggu_rata2)
                    ELSE 0
                END as safety_stock_bahan,
                
                -- ROP: (demand_daily * lead_time) + safety_stock
                CASE 
                    WHEN s.permintaan_harian_rata2 > 0 AND s.waktu_tunggu_rata2 > 0 THEN
                        (s.permintaan_harian_rata2 * s.waktu_tunggu_rata2) + 
                        (1.65 * s.permintaan_stddev * SQRT(s.waktu_tunggu_rata2))
                    ELSE 0
                END as rop_bahan
                
            FROM v_bahan_baku_transaction_stats s
        ");
        
        // LAYER 3: Stock Status View
        // Adds status flags and alerts based on Layer 2 metrics
        DB::statement("
            CREATE OR REPLACE VIEW v_bahan_baku_stock_status AS
            SELECT 
                m.*,
                
                -- Stock status classification
                CASE 
                    WHEN m.stok_bahan > m.rop_bahan THEN 'Aman'
                    WHEN m.stok_bahan > m.safety_stock_bahan THEN 'Rendah'
                    WHEN m.stok_bahan > 0 THEN 'Kritis'
                    ELSE 'Habis'
                END as status_stok,
                
                -- Days until stockout (at current demand rate)
                CASE 
                    WHEN m.permintaan_harian_rata2 > 0 THEN 
                        FLOOR(m.stok_bahan / m.permintaan_harian_rata2)
                    ELSE 999
                END as hari_sampai_habis,
                
                -- Reorder flag
                CASE 
                    WHEN m.stok_bahan <= m.rop_bahan THEN 'Ya'
                    ELSE 'Tidak'
                END as perlu_reorder,
                
                -- Stock value
                (m.stok_bahan * m.harga_bahan) as nilai_stok
                
            FROM v_bahan_baku_inventory_metrics m
        ");
        
        // BONUS: Dashboard Summary View
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_inventory_summary AS
            SELECT 
                COUNT(CASE WHEN status_stok = 'Aman' THEN 1 END) as stok_aman_count,
                COUNT(CASE WHEN status_stok = 'Rendah' THEN 1 END) as stok_rendah_count,
                COUNT(CASE WHEN status_stok = 'Kritis' THEN 1 END) as stok_kritis_count,
                COUNT(CASE WHEN status_stok = 'Habis' THEN 1 END) as stok_habis_count,
                COUNT(CASE WHEN perlu_reorder = 'Ya' THEN 1 END) as perlu_reorder_count,
                SUM(nilai_stok) as total_nilai_stok,
                AVG(eoq_bahan) as avg_eoq,
                AVG(safety_stock_bahan) as avg_safety_stock,
                AVG(rop_bahan) as avg_rop
            FROM v_bahan_baku_stock_status
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_dashboard_inventory_summary');
        DB::statement('DROP VIEW IF EXISTS v_bahan_baku_stock_status');
        DB::statement('DROP VIEW IF EXISTS v_bahan_baku_inventory_metrics');
        DB::statement('DROP VIEW IF EXISTS v_bahan_baku_transaction_stats');
    }
};
