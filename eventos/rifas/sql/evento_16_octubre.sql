/* =====================================================================
   EVENTO COMERCIAL - VIERNES 16 DE OCTUBRE
   Tablas de control para las dinámicas:
     - Electromenores (hasta las 2:00 pm, stock limitado)
     - Sorteos de la tarde (2, 3, 4 y 5 pm)
   La dinámica de premios mayores (#1 / #2) se registra en
   T_USUARIOS.BOLETAS1 (no requiere tabla nueva).
   ===================================================================== */

IF OBJECT_ID('dbo.T_RIFA_ELECTROMENORES') IS NULL
BEGIN
  CREATE TABLE [dbo].[T_RIFA_ELECTROMENORES] (
    [ID] int IDENTITY(1, 1) NOT NULL,
    [PASSPORT] varchar(10) COLLATE Modern_Spanish_CI_AS NOT NULL,
    [NOMBRES] varchar(120) COLLATE Modern_Spanish_CI_AS NULL,
    [N_LABS] int DEFAULT 0 NOT NULL,
    [COMPRA] numeric(18, 0) DEFAULT 0 NOT NULL,
    [USUARIO] varchar(16) COLLATE Modern_Spanish_CI_AS NULL,
    [FECHA_HORA] datetime DEFAULT getdate() NOT NULL,
    CONSTRAINT [T_RIFA_ELECTROMENORES_pk] PRIMARY KEY CLUSTERED ([ID]),
    CONSTRAINT [T_RIFA_ELECTROMENORES_uq] UNIQUE ([PASSPORT])
  )
  ON [PRIMARY]
END
GO

IF OBJECT_ID('dbo.T_RIFA_SORTEOS_GANADORES') IS NULL
BEGIN
  CREATE TABLE [dbo].[T_RIFA_SORTEOS_GANADORES] (
    [ID] int IDENTITY(1, 1) NOT NULL,
    [HORA] char(5) COLLATE Modern_Spanish_CI_AS NOT NULL,
    [MONTO_MINIMO] numeric(18, 0) DEFAULT 0 NOT NULL,
    [PASSPORT] varchar(10) COLLATE Modern_Spanish_CI_AS NOT NULL,
    [NOMBRES] varchar(120) COLLATE Modern_Spanish_CI_AS NULL,
    [COMPRA] numeric(18, 0) DEFAULT 0 NOT NULL,
    [PREMIO] numeric(18, 0) DEFAULT 0 NOT NULL,
    [ANULADO] bit DEFAULT 0 NOT NULL,
    [USUARIO] varchar(16) COLLATE Modern_Spanish_CI_AS NULL,
    [FECHA_HORA] datetime DEFAULT getdate() NOT NULL,
    CONSTRAINT [T_RIFA_SORTEOS_GANADORES_pk] PRIMARY KEY CLUSTERED ([ID]),
    CONSTRAINT [T_RIFA_SORTEOS_GANADORES_uq] UNIQUE ([HORA], [PASSPORT])
  )
  ON [PRIMARY]
END
GO

/* Opcional: si T_USUARIOS trae BOLETAS1 de eventos anteriores, reiniciar antes del evento
UPDATE T_USUARIOS SET BOLETAS1 = 0 WHERE ISNULL(BOLETAS1, 0) <> 0
GO
*/

/* Índice recomendado para que las consultas por cliente sean rápidas
   (ajustar si T_PEDIDOS ya tiene uno similar) */
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_T_PEDIDOS_PASSPORT_EV')
  CREATE NONCLUSTERED INDEX [IX_T_PEDIDOS_PASSPORT_EV] ON [dbo].[T_PEDIDOS] ([PASSPORT])
  INCLUDE ([CODIGO], [VALOR_TOTAL], [FECHA_HORA])
GO
