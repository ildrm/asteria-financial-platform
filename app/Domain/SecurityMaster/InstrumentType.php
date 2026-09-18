<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Domain\SecurityMaster;

enum InstrumentType: string
{
    case CommonEquity = 'COMMON_EQUITY';
    case PreferredEquity = 'PREFERRED_EQUITY';
    case Etf = 'ETF';
    case Etn = 'ETN';
    case MutualFund = 'MUTUAL_FUND';
    case ClosedEndFund = 'CLOSED_END_FUND';
    case GovernmentBond = 'GOVERNMENT_BOND';
    case CorporateBond = 'CORPORATE_BOND';
    case MunicipalBond = 'MUNICIPAL_BOND';
    case AgencyBond = 'AGENCY_BOND';
    case SupranationalBond = 'SUPRANATIONAL_BOND';
    case AssetBackedSecurity = 'ABS';
    case MortgageBackedSecurity = 'MBS';
    case InflationLinkedBond = 'INFLATION_LINKED_BOND';
    case FloatingRateNote = 'FLOATING_RATE_NOTE';
    case MoneyMarket = 'MONEY_MARKET';
    case FxSpot = 'FX_SPOT';
    case FxForward = 'FX_FORWARD';
    case FxSwap = 'FX_SWAP';
    case Ndf = 'NDF';
    case Future = 'FUTURE';
    case Option = 'OPTION';
    case Swap = 'SWAP';
    case Swaption = 'SWAPTION';
    case CreditDefaultSwap = 'CDS';
    case Commodity = 'COMMODITY';
    case CryptoAsset = 'CRYPTO_ASSET';
    case CryptoPair = 'CRYPTO_PAIR';
    case Index = 'INDEX';
    case StructuredProduct = 'STRUCTURED_PRODUCT';
    case PrivateCompany = 'PRIVATE_COMPANY';
    case PrivateInvestment = 'PRIVATE_INVESTMENT';
}
